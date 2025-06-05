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

use App\Models\Foundation\Summit\Events\Presentations\TrackChairs\PresentationTrackChairScore;
use App\Models\Foundation\Summit\Events\Presentations\TrackChairs\PresentationTrackChairScoreType;
use models\summit\Presentation;
use models\summit\SummitTrackChair;
use Tests\InsertSummitTestData;
use Tests\TestCase;

class SummitTrackChairTest extends TestCase
{
    use InsertSummitTestData;

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


    public function testAddSummitTrackChair(){
        // Create a new track chair
        $track_chair = TestUtils::mockSummitTrackChair(self::$summit);
        self::$em->persist($track_chair);
        
        // Create a new member
        $member = TestUtils::mockMember();
        self::$em->persist($member);
        
        // Set the member (ManyToOne relationship)
        $track_chair->setMember($member);
        
        // Create a new category
        $category = TestUtils::mockPresentationCategory(self::$summit);
        $category->setChairVisible(true);
        self::$em->persist($category);
        
        // Add the category (ManyToMany relationship)
        $track_chair->addCategory($category);
        
        // Create a new presentation
        $presentation = new Presentation();
        $presentation->setTitle("Test Presentation " . str_random(5));
        $presentation->setAbstract("Test Abstract " . str_random(5));
        $presentation->setCategory(self::$defaultTrack);
        $presentation->setType(self::$defaultPresentationType);
        $presentation->setSummit(self::$summit);
        self::$em->persist($presentation);
        
        // Create a score type
        $score_type = new PresentationTrackChairScoreType();
        $score_type->setName("Test Score Type " . str_random(5));
        $score_type->setDescription("Test Score Type " . str_random(5));
        $score_type->setScore(5);
        self::$em->persist($score_type);

        // Create a rating type
        $rating_type = self::$default_selection_plan->getTrackChairRatingTypes()[0];
        $rating_type->addScoreType($score_type);

        // Create a score
        $score = new PresentationTrackChairScore();
        $score->setPresentation($presentation);
        $score->setType($score_type);

        // Add the score (OneToMany relationship)
        $track_chair->addScore($score);
        
        self::$em->flush();
        self::$em->clear();
        
        // Retrieve the track chair from the database
        $repository = self::$em->getRepository(SummitTrackChair::class);
        $found_track_chair = $repository->find($track_chair->getId());
        
        // Test ManyToOne relationship with member
        $found_member = $found_track_chair->getMember();
        $this->assertEquals($member->getId(), $found_member->getId());
        
        // Test ManyToMany relationship with categories
        $found_categories = $found_track_chair->getCategories()->toArray();
        $found_category = null;
        foreach ($found_categories as $cat) {
            if ($cat->getCode() === $category->getCode()) {
                $found_category = $cat;
                break;
            }
        }
        $this->assertNotNull($found_category);
        
        // Test OneToMany relationship with scores
        $found_score = $found_track_chair->getScoreByRatingTypeAndPresentation($rating_type, $presentation);
        $this->assertNotEmpty($found_score);
        $this->assertEquals($score->getPresentation()->getId(), $found_score->getPresentation()->getId());
        $this->assertEquals($score->getType()->getId(), $found_score->getType()->getId());

    }
}