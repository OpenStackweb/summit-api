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

use Mockery;
use models\oauth2\IResourceServerContext;
use models\summit\PresentationSpeaker;
use models\summit\Summit;
use ModelSerializers\AdminPresentationSpeakerSerializer;
use ModelSerializers\PresentationSpeakerSerializer;

/**
 * Rejected submissions must not be exposed by the public speakers API
 * (expand=rejected_presentations); the admin serializer keeps exposing them.
 * @package Tests
 */
final class PresentationSpeakerRejectedPresentationsTest extends TestCase
{
    public function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function buildSpeaker(): PresentationSpeaker
    {
        $speaker = Mockery::mock(PresentationSpeaker::class)->shouldIgnoreMissing();
        $speaker->shouldReceive('hasMember')->andReturn(false);
        $speaker->shouldReceive('getRejectedPresentationIds')->andReturn([11, 12]);
        $speaker->shouldReceive('getRejectedPresentations')->andReturn([]);
        return $speaker;
    }

    private function serialize(string $class): array
    {
        $serializer = new $class($this->buildSpeaker(), Mockery::mock(IResourceServerContext::class)->shouldIgnoreMissing());
        return $serializer->serialize(
            'rejected_presentations',
            [],
            ['rejected_presentations'],
            ['summit' => Mockery::mock(Summit::class)->shouldIgnoreMissing()]
        );
    }

    public function testPublicSerializerDoesNotExposeRejectedPresentations()
    {
        $values = $this->serialize(PresentationSpeakerSerializer::class);
        $this->assertArrayNotHasKey('rejected_presentations', $values);
    }

    public function testAdminSerializerStillExposesRejectedPresentations()
    {
        $values = $this->serialize(AdminPresentationSpeakerSerializer::class);
        $this->assertArrayHasKey('rejected_presentations', $values);
    }
}
