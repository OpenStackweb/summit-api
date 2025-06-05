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
use models\main\File;
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
}