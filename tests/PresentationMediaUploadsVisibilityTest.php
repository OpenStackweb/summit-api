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

use App\Security\SummitScopes;
use Doctrine\Common\Collections\ArrayCollection;
use Illuminate\Support\Facades\Cache;
use models\main\Member;
use models\oauth2\IResourceServerContext;
use models\summit\Presentation;
use models\summit\PresentationMediaUpload;
use ModelSerializers\PresentationSerializer;
use Mockery;

/**
 * One test per branch of PresentationSerializer::getMediaUploadsSerializerType(), asserted
 * through the observable output of serialize() rather than through the serializer-type string
 * that method returns: what the PR this covers is actually about is which uploads reach which
 * caller, and the type is only the mechanism.
 *
 * The relations=media_uploads shape is used throughout because it yields a bare id list, so an
 * assertion names the exact uploads without dragging PresentationMediaUploadSerializer, storage
 * backends and public_url generation into a unit test.
 *
 * @package Tests
 */
final class PresentationMediaUploadsVisibilityTest extends TestCase
{
    /**
     * display_on_site = true. The only upload a caller without privilege may ever see.
     */
    const ApprovedUploadId = 101;

    /**
     * display_on_site = false, which is what PresentationMaterial::__construct defaults to, so
     * this is the shape of every upload that has not been through the admin approval checkbox.
     */
    const DraftUploadId = 102;

    public function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * @param int $identifier unique per test method: the serializer builds its cache key from
     * the presentation id, and although use_cache is off here, keeping ids distinct means a
     * future caching change cannot make one test's result depend on another's having run.
     * @param bool $member_can_edit what Presentation::memberCanEdit() answers for the member
     * passed in the test - creator, moderator or speaker on this presentation.
     * @return Presentation
     */
    private function buildPresentation(int $identifier, bool $member_can_edit = false): Presentation
    {
        $approved = Mockery::mock(PresentationMediaUpload::class);
        $approved->shouldReceive('getId')->andReturn(self::ApprovedUploadId);
        $approved->shouldReceive('getDisplayOnSite')->andReturn(true);

        $draft = Mockery::mock(PresentationMediaUpload::class);
        $draft->shouldReceive('getId')->andReturn(self::DraftUploadId);
        $draft->shouldReceive('getDisplayOnSite')->andReturn(false);

        $presentation = Mockery::mock(Presentation::class);
        $presentation->shouldReceive('getId')->andReturn($identifier);
        $presentation->shouldReceive('getLastEditedUTC')->andReturn(null);
        $presentation->shouldReceive('getMediaUploads')
            ->andReturn(new ArrayCollection([$approved, $draft]));
        $presentation->shouldReceive('memberCanEdit')->andReturn($member_can_edit);

        return $presentation;
    }

    /**
     * A member-backed caller: a browser client carrying a user token.
     * @param bool $is_admin global administrator.
     * @param bool $is_summit_admin summit-front-end-administrators, the show-admin operators.
     * @return IResourceServerContext
     */
    private function buildMemberContext(bool $is_admin = false, bool $is_summit_admin = false): IResourceServerContext
    {
        $member = Mockery::mock(Member::class);
        $member->shouldReceive('isAdmin')->andReturn($is_admin);
        $member->shouldReceive('isSummitAdmin')->andReturn($is_summit_admin);

        $context = Mockery::mock(IResourceServerContext::class);
        $context->shouldReceive('getApplicationType')->andReturn('JS_CLIENT');
        $context->shouldReceive('getCurrentUser')->andReturn($member);

        return $context;
    }

    /**
     * A client_credentials caller. getCurrentUser() is null by construction - there is no user
     * behind the token - which is why the scope, and not the member, is what grants access here.
     * @param bool $with_scope whether the token carries ReadAllPresentationMediaUploads.
     * @return IResourceServerContext
     */
    private function buildServiceContext(bool $with_scope): IResourceServerContext
    {
        $scopes = ['%s/summits/read'];
        if ($with_scope) $scopes[] = SummitScopes::ReadAllPresentationMediaUploads;

        $context = Mockery::mock(IResourceServerContext::class);
        $context->shouldReceive('getApplicationType')
            ->andReturn(IResourceServerContext::ApplicationType_Service);
        $context->shouldReceive('getCurrentScope')->andReturn($scopes);
        $context->shouldReceive('getCurrentUser')->andReturn(null);

        return $context;
    }

    /**
     * @param Presentation $presentation
     * @param IResourceServerContext $context
     * @param array $params forwarded to serialize(), which is where use_cache is read.
     * @return array the media upload ids this caller receives
     */
    private function serializeMediaUploadIds(
        Presentation $presentation,
        IResourceServerContext $context,
        array $params = []
    ): array
    {
        $serializer = new PresentationSerializer($presentation, $context);
        // fields is narrowed to id so the attribute-mapping loop in AbstractSerializer only
        // reaches Presentation::getId(); every other mapped getter is irrelevant here and would
        // otherwise have to be stubbed for no gain.
        $values = $serializer->serialize(null, ['id'], ['media_uploads'], $params);

        $this->assertArrayHasKey('media_uploads', $values);
        return $values['media_uploads'];
    }

    public function testAnonymousCallerSeesOnlyApprovedUploads()
    {
        $context = Mockery::mock(IResourceServerContext::class);
        $context->shouldReceive('getApplicationType')->andReturn('JS_CLIENT');
        $context->shouldReceive('getCurrentUser')->andReturn(null);

        $ids = $this->serializeMediaUploadIds($this->buildPresentation(90101), $context);

        $this->assertSame([self::ApprovedUploadId], $ids);
    }

    public function testPlainAttendeeSeesOnlyApprovedUploads()
    {
        $ids = $this->serializeMediaUploadIds(
            $this->buildPresentation(90102, false),
            $this->buildMemberContext()
        );

        $this->assertSame([self::ApprovedUploadId], $ids);
    }

    public function testSpeakerOnThePresentationSeesDraftUploads()
    {
        $ids = $this->serializeMediaUploadIds(
            $this->buildPresentation(90103, true),
            $this->buildMemberContext()
        );

        $this->assertSame([self::ApprovedUploadId, self::DraftUploadId], $ids);
    }

    /**
     * The regression this pins: before the fix a summit admin resolved Public here while
     * OAuth2SummitEventsApiController::getSerializerType() resolved Private for the presentation
     * itself, so the summit-admin event grid stopped showing the upload whose display_on_site
     * checkbox is the only way to approve it.
     */
    public function testSummitAdminSeesDraftUploads()
    {
        $ids = $this->serializeMediaUploadIds(
            $this->buildPresentation(90104, false),
            $this->buildMemberContext(false, true)
        );

        $this->assertSame([self::ApprovedUploadId, self::DraftUploadId], $ids);
    }

    public function testServiceAccountWithScopeSeesDraftUploads()
    {
        $ids = $this->serializeMediaUploadIds(
            $this->buildPresentation(90105),
            $this->buildServiceContext(true)
        );

        $this->assertSame([self::ApprovedUploadId, self::DraftUploadId], $ids);
    }

    /**
     * The other half of that gate: the access is granted by the scope, not by the application
     * type, so a service client without it stays where every other unprivileged caller is.
     */
    public function testServiceAccountWithoutScopeSeesOnlyApprovedUploads()
    {
        $ids = $this->serializeMediaUploadIds(
            $this->buildPresentation(90106),
            $this->buildServiceContext(false)
        );

        $this->assertSame([self::ApprovedUploadId], $ids);
    }

    /**
     * A cached entry that reports as present and then reads back as unavailable must degrade to
     * a fresh build, not to an error. The entry can expire on its own TTL, be evicted under
     * memory pressure, or be dropped by a cache flush, and none of that is rare enough on the
     * voteable-presentation endpoints - the only ones that pass use_cache - to leave unhandled.
     *
     * The assertion is on the payload rather than on how the cache was consulted, so it holds
     * whether the read is one call or two.
     */
    public function testUnavailableCachedValueIsTreatedAsAMiss()
    {
        Cache::shouldReceive('has')->zeroOrMoreTimes()->andReturn(true);
        Cache::shouldReceive('get')->zeroOrMoreTimes()->andReturn(null);
        Cache::shouldReceive('put')->zeroOrMoreTimes()->andReturn(true);

        $context = Mockery::mock(IResourceServerContext::class);
        $context->shouldReceive('getApplicationType')->andReturn('JS_CLIENT');
        $context->shouldReceive('getCurrentUser')->andReturn(null);

        $ids = $this->serializeMediaUploadIds(
            $this->buildPresentation(90107),
            $context,
            ['use_cache' => true]
        );

        $this->assertSame([self::ApprovedUploadId], $ids);
    }

    /**
     * The other side of that read: a decodable entry is still served from cache, and the
     * media_uploads on it are still resolved for the caller asking now rather than taken from
     * whoever populated the key. Without this the previous test would pass just as well against
     * a serializer that had stopped reading the cache altogether.
     */
    public function testCacheHitIsServedButMediaUploadsAreResolvedFresh()
    {
        // A payload as it is stored: media_uploads is absent by construction, and the stale value
        // is one no unprivileged caller may receive.
        Cache::shouldReceive('has')->zeroOrMoreTimes()->andReturn(true);
        Cache::shouldReceive('get')->zeroOrMoreTimes()->andReturn(
            json_encode(['id' => 90108, 'title' => 'from cache', 'media_uploads' => [self::DraftUploadId]])
        );
        Cache::shouldReceive('put')->zeroOrMoreTimes()->andReturn(true);

        $context = Mockery::mock(IResourceServerContext::class);
        $context->shouldReceive('getApplicationType')->andReturn('JS_CLIENT');
        $context->shouldReceive('getCurrentUser')->andReturn(null);

        $serializer = new PresentationSerializer($this->buildPresentation(90108), $context);
        $values = $serializer->serialize(null, ['id'], ['media_uploads'], ['use_cache' => true]);

        // Came off the cached payload: parent::serialize() was never reached, and it does not
        // produce this field under fields=['id'] anyway.
        $this->assertSame('from cache', $values['title']);
        // ...but this one did not.
        $this->assertSame([self::ApprovedUploadId], $values['media_uploads']);
    }
}
