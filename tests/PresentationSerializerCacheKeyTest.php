<?php namespace Tests;
/*
 * Copyright 2026 OpenStack Foundation
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 * http://www.apache.org/licenses/LICENSE-2.0
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 **/

use Doctrine\Common\Collections\ArrayCollection;
use Illuminate\Support\Facades\Cache;
use models\oauth2\IResourceServerContext;
use models\summit\Presentation;
use ModelSerializers\AdminPresentationSerializer;
use ModelSerializers\PresentationSerializer;
use Mockery;

/**
 * What separates one cached presentation payload from another. Distinct from the media-uploads
 * visibility suite: that one covers a field deliberately kept out of the cache, this one covers
 * everything that stays in it and therefore has to be told apart by the key.
 *
 * Tests observe how many cache slots a sequence of requests consumes and what comes back out of
 * them, never the key itself - the guarantee is that two requests deserving different payloads
 * do not share an entry, not that the key is spelled any particular way.
 *
 * @package Tests
 */
final class PresentationSerializerCacheKeyTest extends TestCase
{
    /**
     * Cache entries written during a test, keyed exactly as production wrote them.
     * @var array
     */
    private array $store = [];

    public function setUp(): void
    {
        parent::setUp();
        $this->store = [];
        // A stateful fake rather than the configured driver: it keeps the test off redis/file,
        // and the entry count is the whole point of these assertions.
        Cache::shouldReceive('put')->andReturnUsing(function ($key, $value, $ttl = null) {
            $this->store[$key] = $value;
            return true;
        });
        Cache::shouldReceive('get')->andReturnUsing(function ($key, $default = null) {
            return $this->store[$key] ?? $default;
        });
        Cache::shouldReceive('has')->andReturnUsing(function ($key) {
            return array_key_exists($key, $this->store);
        });
    }

    public function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * @param int $identifier
     * @return Presentation
     */
    private function buildPresentation(int $identifier): Presentation
    {
        $presentation = Mockery::mock(Presentation::class);
        $presentation->shouldReceive('getId')->andReturn($identifier);
        $presentation->shouldReceive('getLastEditedUTC')->andReturn(null);
        $presentation->shouldReceive('getTitle')->andReturn('a presentation');
        $presentation->shouldReceive('getRank')->andReturn(7);
        $presentation->shouldReceive('getMediaUploads')->andReturn(new ArrayCollection([]));
        $presentation->shouldReceive('getSlides')->andReturn([]);
        $presentation->shouldReceive('memberCanEdit')->andReturn(false);
        // Reached only by testExpandOrderIsNotNormalised. getType() answering null short-circuits
        // the moderator case before it asks for a moderator, which keeps the fixture to the two
        // getters the expand dispatch actually needs.
        $presentation->shouldReceive('getSpeakers')->andReturn([]);
        $presentation->shouldReceive('getType')->andReturn(null);
        return $presentation;
    }

    /**
     * An unauthenticated caller: whatever ends up in the cache, this is who must not receive the
     * admin shape of it.
     * @return IResourceServerContext
     */
    private function buildPublicContext(): IResourceServerContext
    {
        $context = Mockery::mock(IResourceServerContext::class);
        $context->shouldReceive('getApplicationType')->andReturn('JS_CLIENT');
        $context->shouldReceive('getCurrentUser')->andReturn(null);
        return $context;
    }

    /**
     * The reason this ticket exists. AdminPresentationSerializer does not override serialize(),
     * so it writes through the inherited caching path, and its attribute mappings are merged on
     * top of the public ones - rank, selection_status, streaming_url, etherpad_link,
     * overflow_stream_key, chair scores and vote stats all land in the stored payload. With no
     * class component in the key, a public caller repeating the same query params inside the TTL
     * reads that payload back verbatim.
     */
    public function testAdminPayloadIsNotServedToAPublicCaller()
    {
        $presentation = $this->buildPresentation(90201);
        $context = $this->buildPublicContext();
        $arguments = [null, ['id', 'rank'], [], ['use_cache' => true]];

        $admin = (new AdminPresentationSerializer($presentation, $context))->serialize(...$arguments);
        // Sanity: the field really is admin-only, so the assertion below is about the cache and
        // not about a field nobody emits.
        $this->assertSame(7, $admin['rank']);

        $public = (new PresentationSerializer($presentation, $context))->serialize(...$arguments);

        $this->assertArrayNotHasKey('rank', $public);
        $this->assertCount(2, $this->store);
    }

    /**
     * The key's parts used to be joined with "_", a character that occurs inside the values it
     * joins - media_uploads, extra_questions, selection_plan, public_comments. Two different
     * requests could therefore render the same key: expand=media_uploads&fields=x and
     * expand=media&fields=uploads_x both flattened to "..._media_uploads_x_".
     *
     * Here the second request asks for a field that matches no mapping, so its correct payload is
     * empty; anything it comes back with was somebody else's.
     */
    public function testRequestsThatFlattenAlikeDoNotShareAnEntry()
    {
        $presentation = $this->buildPresentation(90202);
        $context = $this->buildPublicContext();

        $first = (new PresentationSerializer($presentation, $context))
            ->serialize(null, ['id'], ['media_uploads'], ['use_cache' => true]);
        $this->assertSame(90202, $first['id']);

        $second = (new PresentationSerializer($presentation, $context))
            ->serialize(null, ['id_media'], ['uploads'], ['use_cache' => true]);

        $this->assertArrayNotHasKey('id', $second);
        $this->assertCount(2, $this->store);
    }

    /**
     * fields and relations are both consumed with in_array(), so their order cannot change the
     * payload. Leaving them unsorted just spends a second entry on a request already answered.
     */
    public function testFieldAndRelationOrderReuseTheSameEntry()
    {
        $presentation = $this->buildPresentation(90203);
        $context = $this->buildPublicContext();

        (new PresentationSerializer($presentation, $context))
            ->serialize(null, ['id', 'title'], ['slides', 'media_uploads'], ['use_cache' => true]);
        (new PresentationSerializer($presentation, $context))
            ->serialize(null, ['title', 'id'], ['media_uploads', 'slides'], ['use_cache' => true]);

        $this->assertCount(1, $this->store);
    }

    /**
     * expand is deliberately left alone. Its relations are dispatched in the order given, and the
     * speakers and moderator cases both write $values['moderator'] while disagreeing about
     * moderator_speaker_id - speakers reads it, moderator unsets it - so the order is capable of
     * changing the result. Normalising it would quietly merge two payloads that are allowed to
     * differ, which is the failure this whole ticket is about.
     */
    public function testExpandOrderIsNotNormalised()
    {
        $presentation = $this->buildPresentation(90204);
        $context = $this->buildPublicContext();

        (new PresentationSerializer($presentation, $context))
            ->serialize('speakers,moderator', ['id'], [], ['use_cache' => true]);
        (new PresentationSerializer($presentation, $context))
            ->serialize('moderator,speakers', ['id'], [], ['use_cache' => true]);

        $this->assertCount(2, $this->store);
    }
}
