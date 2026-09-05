<?php

namespace Tests\Unit\Entities;

/**
 * Copyright 2025 OpenStack Foundation
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

use App\Models\Foundation\Main\Language;
use Illuminate\Support\Facades\Config;
use Mockery;
use models\main\File;
use models\main\Member;
use models\summit\Presentation;
use models\summit\PresentationSpeaker;
use models\summit\SpeakerExpertise;
use models\summit\SpeakerPresentationLink;
use models\summit\SpeakerTravelPreference;
use Tests\InsertMemberTestData;
use Tests\InsertSummitTestData;
use Tests\TestCase;

class PresentationSpeakerTest extends TestCase
{
    use InsertSummitTestData;
    use InsertMemberTestData;

    /**
     * @throws \Exception
     */
    protected function setUp():void
    {
        parent::setUp();
        self::insertSummitTestData();
    }

    public function tearDown():void
    {
        Mockery::close();
        self::clearSummitTestData();
        parent::tearDown();
    }

    public function testPersistPresentationSpeaker(){
        $repository = self::$em->getRepository(PresentationSpeaker::class);

        $speaker = new PresentationSpeaker();
        $speaker->setFirstName("Test");
        $speaker->setLastName("Speaker");
        $speaker->setBio("This is the Bio");
        self::$em->persist($speaker);

        // Create a new file for photo
        $photo = new File();
        $photo->setName("Test Photo " . str_random(5));
        $photo->setFilename("test_photo_" . str_random(5) . ".jpg");
        self::$em->persist($photo);

        // Set the photo (ManyToOne relationship)
        $speaker->setPhoto($photo);

        $presentation = self::$summit->getPresentations()[1];

        // Add the presentation (ManyToMany relationship)
        $speaker->addPresentation($presentation);

        // Create a new presentation link
        $link = new SpeakerPresentationLink(
            "https://test-link-" . str_random(5) . ".com",
            "Test Link " . str_random(5));
        self::$em->persist($link);

        // Add the presentation link (OneToMany relationship)
        $speaker->addOtherPresentationLink($link);

        self::$em->flush();
        self::$em->clear();

        // Retrieve the speaker from the database
        $found_speaker = $repository->find($speaker->getId());

        // Test basic properties
        $this->assertEquals($speaker->getFirstName(), $found_speaker->getFirstName());

        // Test ManyToOne relationship with photo
        $found_photo = $found_speaker->getPhoto();
        $this->assertEquals($photo->getId(), $found_photo->getId());

        // Test ManyToMany relationship with presentations
        $found_presentations = $found_speaker->getPresentations(self::$summit->getId());
        $this->assertCount(1, $found_presentations);

        // Test OneToMany relationship with presentation links
        $found_links = $found_speaker->getOtherPresentationLinks()->toArray();
        $this->assertNotEmpty($found_links);
        $found_link = null;
        foreach ($found_links as $l) {
            if ($l->getTitle() === $link->getTitle()) {
                $found_link = $l;
                break;
            }
        }
        $this->assertNotNull($found_link);

        $speaker = $repository->find($speaker->getId());

        // Clear relationships
        $speaker->clearPresentations();
        $speaker->clearAreasOfExpertise();
        $speaker->clearOtherPresentationLinks();
        $speaker->clearTravelPreferences();
        $speaker->clearLanguages();
        $speaker->clearPhoto();

        self::$em->flush();
        self::$em->clear();

        // Retrieve the speaker from the database
        $found_speaker = $repository->find($speaker->getId());

        // Test ManyToOne relationship with photo
        $this->assertFalse($found_speaker->hasPhoto());

        // Test ManyToMany relationship with presentations
        $this->assertEmpty($found_speaker->getPresentations(self::$summit->getId()));

        // Test ManyToMany relationship with areas of expertise
        $this->assertEmpty($found_speaker->getAreasOfExpertise()->toArray());

        // Test OneToMany relationship with presentation links
        $this->assertEmpty($found_speaker->getOtherPresentationLinks()->toArray());

        // Test ManyToMany relationship with travel preferences
        $this->assertEmpty($found_speaker->getTravelPreferences()->toArray());

        // Test ManyToMany relationship with languages
        $this->assertEmpty($found_speaker->getLanguages()->toArray());

    }

    /**
     * Policy Rule 9: the name fallback to the linked Member must skip that Member's value
     * (leaving the speaker's own field blank) when the Member's own visibility toggle is off.
     */
    public function testNameFallbackSkipsMemberWhenAccountFullnameToggleIsOff()
    {
        $member = Mockery::mock(Member::class);
        $member->shouldReceive('getId')->andReturn(42);
        $member->shouldReceive('setSpeaker')->andReturnNull();
        $member->shouldReceive('isPublicProfileShowFullname')->andReturn(false);
        // stubbed (not just omitted) so a missing gate surfaces this value instead of an
        // uncaught Mockery exception, which would falsely look like the gate held.
        $member->shouldReceive('getFirstName')->andReturn('Ada');
        $member->shouldReceive('getLastName')->andReturn('Lovelace');
        $member->shouldReceive('getFullName')->andReturn('Ada Lovelace');

        $speaker = new PresentationSpeaker();
        $speaker->setMember($member);

        $this->assertEmpty($speaker->getFirstName());
        $this->assertEmpty($speaker->getLastName());
        $this->assertEmpty($speaker->getFullName());
    }

    /**
     * Policy Rule 9: the name fallback still applies the Member's own value when that
     * Member's visibility toggle is on.
     */
    public function testNameFallbackUsesMemberWhenAccountFullnameToggleIsOn()
    {
        $member = Mockery::mock(Member::class);
        $member->shouldReceive('getId')->andReturn(42);
        $member->shouldReceive('setSpeaker')->andReturnNull();
        $member->shouldReceive('isPublicProfileShowFullname')->andReturn(true);
        $member->shouldReceive('getFirstName')->andReturn('Ada');
        $member->shouldReceive('getLastName')->andReturn('Lovelace');
        $member->shouldReceive('getFullName')->andReturn('Ada Lovelace');

        $speaker = new PresentationSpeaker();
        $speaker->setMember($member);

        $this->assertSame('Ada', $speaker->getFirstName());
        $this->assertSame('Lovelace', $speaker->getLastName());
        $this->assertSame('Ada Lovelace', $speaker->getFullName());
    }

    /**
     * Policy Rule 9: the photo fallback to the linked Member must skip that Member's photo
     * (continuing to the configured default image) when the Member's own visibility toggle is off.
     */
    public function testPhotoFallbackSkipsMemberWhenAccountPhotoToggleIsOff()
    {
        $photo = Mockery::mock(File::class);
        $photo->shouldReceive('getUrl')->andReturn('https://example.com/member-photo.jpg');

        $member = Mockery::mock(Member::class);
        $member->shouldReceive('getId')->andReturn(42);
        $member->shouldReceive('setSpeaker')->andReturnNull();
        $member->shouldReceive('isPublicProfileShowPhoto')->andReturn(false);
        // stubbed (not just omitted) so a missing gate surfaces this photo instead of silently
        // passing: both getProfilePhotoUrl()/getBigProfilePhotoUrl() wrap this branch in a
        // try/catch that would swallow an unstubbed-call exception and mask a missing gate.
        $member->shouldReceive('hasPhoto')->andReturn(true);
        $member->shouldReceive('getPhoto')->andReturn($photo);

        $speaker = new PresentationSpeaker();
        $speaker->setMember($member);

        $default_pic = Config::get("app.default_profile_image", null);
        $this->assertSame($default_pic, $speaker->getProfilePhotoUrl());
        $this->assertSame($default_pic, $speaker->getBigProfilePhotoUrl());
    }

    /**
     * Policy Rule 9: the photo fallback still applies the Member's own photo when that
     * Member's visibility toggle is on.
     */
    public function testPhotoFallbackUsesMemberWhenAccountPhotoToggleIsOn()
    {
        $photo = Mockery::mock(File::class);
        $photo->shouldReceive('getUrl')->andReturn('https://example.com/member-photo.jpg');

        $member = Mockery::mock(Member::class);
        $member->shouldReceive('getId')->andReturn(42);
        $member->shouldReceive('setSpeaker')->andReturnNull();
        $member->shouldReceive('isPublicProfileShowPhoto')->andReturn(true);
        $member->shouldReceive('hasPhoto')->andReturn(true);
        $member->shouldReceive('getPhoto')->andReturn($photo);

        $speaker = new PresentationSpeaker();
        $speaker->setMember($member);

        $this->assertSame('https://example.com/member-photo.jpg', $speaker->getProfilePhotoUrl());
        $this->assertSame('https://example.com/member-photo.jpg', $speaker->getBigProfilePhotoUrl());
    }
}