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

use models\main\Member;
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
        parent::tearDown();
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

    public function testBioGatedSpeakerFieldsAreNotMaskedWhenAccountBioToggleIsOff()
    {
        $member = Mockery::mock(Member::class);
        $member->shouldReceive('getGender')->andReturn('Female');

        $speaker = Mockery::mock(PresentationSpeaker::class)->makePartial();
        $speaker->shouldReceive('hasMember')->andReturn(true);
        $speaker->shouldReceive('getMember')->andReturn($member);
        $speaker->shouldReceive('getBio')->andReturn('A speaker bio');
        $speaker->shouldReceive('getCompany')->andReturn('Acme Corp');
        $speaker->shouldReceive('getCountry')->andReturn('AR');
        $speaker->shouldReceive('getTitle')->andReturn('CTO');
        // the target speaker's own account-level "show bio" toggle is OFF - populated
        // speaker-profile fields (policy Rule 2) must stay public regardless.
        $speaker->shouldReceive('isPublicProfileShowBio')->andReturn(false);
        $speaker->shouldReceive('isPublicProfileShowEmail')->andReturn(true);
        $speaker->shouldReceive('isPublicProfileShowPhoto')->andReturn(true);

        $resource_server_context = Mockery::mock(IResourceServerContext::class);
        $serializer = new PresentationSpeakerSerializer($speaker, $resource_server_context);

        $values = $serializer->serialize(null, ['bio', 'gender', 'company', 'country', 'title'], ['none']);

        $this->assertSame('A speaker bio', $values['bio']);
        $this->assertSame('Female', $values['gender']);
        $this->assertSame('Acme Corp', $values['company']);
        $this->assertSame('AR', $values['country']);
        $this->assertSame('CTO', $values['title']);
    }

    public function testSocialMediaFieldsAreNotMaskedWhenAccountSocialToggleIsOff()
    {
        $speaker = Mockery::mock(PresentationSpeaker::class)->makePartial();
        $speaker->shouldReceive('hasMember')->andReturn(false);
        $speaker->shouldReceive('getIRCHandle')->andReturn('speaker_nick');
        $speaker->shouldReceive('getTwitterName')->andReturn('@speaker_nick');
        // the target speaker's own account-level "show social media" toggle is OFF - irc/twitter
        // are populated speaker-profile fields (policy Rule 2) and must stay public regardless.
        $speaker->shouldReceive('isPublicProfileShowSocialMediaInfo')->andReturn(false);
        $speaker->shouldReceive('isPublicProfileShowEmail')->andReturn(true);
        $speaker->shouldReceive('isPublicProfileShowPhoto')->andReturn(true);

        $resource_server_context = Mockery::mock(IResourceServerContext::class);
        $serializer = new PresentationSpeakerSerializer($speaker, $resource_server_context);

        $values = $serializer->serialize(null, ['irc', 'twitter'], ['none']);

        $this->assertSame('speaker_nick', $values['irc']);
        $this->assertSame('@speaker_nick', $values['twitter']);
    }

    public function testPhotoUrlsPassThroughSerializerRegardlessOfAccountPhotoToggle()
    {
        $speaker = Mockery::mock(PresentationSpeaker::class)->makePartial();
        $speaker->shouldReceive('hasMember')->andReturn(false);
        $speaker->shouldReceive('getProfilePhotoUrl')->andReturn('https://example.com/pic.jpg');
        $speaker->shouldReceive('getBigProfilePhotoUrl')->andReturn('https://example.com/big_pic.jpg');
        // policy Rule 9: the account-toggle gate on the borrowed-from-account photo fallback now
        // lives inside getProfilePhotoUrl()/getBigProfilePhotoUrl() themselves (ClickUp 86bbmbm0f),
        // not in this serializer - so the serializer must pass their result through unmasked even
        // when the toggle is off, instead of re-applying its own masking on top.
        $speaker->shouldReceive('isPublicProfileShowPhoto')->andReturn(false);
        $speaker->shouldReceive('isPublicProfileShowEmail')->andReturn(true);

        $resource_server_context = Mockery::mock(IResourceServerContext::class);
        $serializer = new PresentationSpeakerSerializer($speaker, $resource_server_context);

        $values = $serializer->serialize(null, ['pic', 'big_pic'], ['none']);

        $this->assertSame('https://example.com/pic.jpg', $values['pic']);
        $this->assertSame('https://example.com/big_pic.jpg', $values['big_pic']);
    }
}
