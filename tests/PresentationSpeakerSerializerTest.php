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

use models\oauth2\IResourceServerContext;
use models\summit\PresentationSpeaker;
use ModelSerializers\PresentationSpeakerSerializer;
use Mockery;

/**
 * Class PresentationSpeakerSerializerTest
 * @package Tests
 */
final class PresentationSpeakerSerializerTest extends TestCase
{
    public function tearDown(): void
    {
        Mockery::close();
    }

    public function testPhoneNumberIsMaskedInPublicContextEvenWhenSpeakerToggleIsOn()
    {
        $speaker = Mockery::mock(PresentationSpeaker::class)->makePartial();
        $speaker->shouldReceive('hasMember')->andReturn(false);
        $speaker->shouldReceive('getPhoneNumber')->andReturn('+1-555-0100');
        // target speaker's own account-level toggle is ON - the exact case that used to leak
        // the raw phone_number regardless of the requesting caller's identity.
        $speaker->shouldReceive('isPublicProfileShowTelephoneNumber')->andReturn(true);
        $speaker->shouldReceive('isPublicProfileShowBio')->andReturn(true);
        $speaker->shouldReceive('isPublicProfileShowEmail')->andReturn(true);
        $speaker->shouldReceive('isPublicProfileShowSocialMediaInfo')->andReturn(true);
        $speaker->shouldReceive('isPublicProfileShowPhoto')->andReturn(true);

        $resource_server_context = Mockery::mock(IResourceServerContext::class);
        $serializer = new PresentationSpeakerSerializer($speaker, $resource_server_context);

        $values = $serializer->serialize(null, ['phone_number'], ['none']);

        $this->assertSame('', $values['phone_number']);
    }
}
