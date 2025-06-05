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

use App\Models\Foundation\ExtraQuestions\ExtraQuestionType;
use App\Models\Foundation\ExtraQuestions\ExtraQuestionTypeConstants;
use App\Models\Foundation\Summit\Events\Presentations\TrackChairs\PresentationTrackChairRatingType;
use App\Models\Foundation\Summit\ExtraQuestions\SummitSelectionPlanExtraQuestionType;
use App\Models\Foundation\Summit\SelectionPlan;
use models\summit\PresentationActionType;
use models\summit\PresentationCategoryGroup;
use models\summit\SummitEventType;
use Tests\InsertSummitTestData;
use Tests\TestCase;

class SelectionPlanTest extends TestCase
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

    public function testAddSelectionPlan(){
        $selection_plan_id = self::$default_selection_plan->getId();
        $repository = self::$em->getRepository(SelectionPlan::class);
        $selection_plan = $repository->find($selection_plan_id);

        // Create a new track group
        $track_group = new PresentationCategoryGroup();
        $track_group->setName("Test Track Group " . str_random(5));
        self::$em->persist($track_group);

        // Add the track group (ManyToMany relationship)
        $selection_plan->addTrackGroup($track_group);

        // Create a new event type
        $event_type = new SummitEventType();
        $event_type->setType("Test Event Type " . str_random(5));
        self::$em->persist($event_type);

        // Add the event type (ManyToMany relationship)
        $selection_plan->addEventType($event_type);

        // Create a new presentation action type
        $action_type = new PresentationActionType();
        $action_type->setLabel("Test Action Type " . str_random(5));
        $action_type->setSummit(self::$summit);
        self::$em->persist($action_type);

        // Add the presentation action type (ManyToMany relationship)
        $selection_plan->addPresentationActionType($action_type);

        // Create a new track chair rating type
        $rating_type = new PresentationTrackChairRatingType();
        $rating_type->setName("Test Rating Type " . str_random(5));
        $rating_type->setWeight(1.0);
        $rating_type->setOrder(1);
        $rating_type->setSelectionPlan($selection_plan);
        self::$em->persist($rating_type);

        // Add the track chair rating type (OneToMany relationship)
        $selection_plan->addTrackChairRatingType($rating_type);

        // Create a new extra question
        $question = new SummitSelectionPlanExtraQuestionType();
        $question->setName("Test Question " . str_random(5));
        $question->setLabel("Test Question Label " . str_random(5));
        $question->setType(ExtraQuestionTypeConstants::CheckBoxQuestionType);
        self::$summit->addSelectionPlanExtraQuestion($question);
        self::$em->persist($question);

        // Add the extra question (ManyToMany relationship)
        $selection_plan->addExtraQuestion($question);

        self::$em->flush();
        self::$em->clear();

        // Retrieve the selection plan from the database
        $found_selection_plan = $repository->find($selection_plan->getId());

        // Test ManyToMany relationship with track groups
        $found_track_groups = $found_selection_plan->getCategoryGroups()->toArray();
        $found_track_group = null;
        foreach ($found_track_groups as $tg) {
            if ($tg->getName() === $track_group->getName()) {
                $found_track_group = $tg;
                break;
            }
        }
        $this->assertNotNull($found_track_group);

        // Test ManyToMany relationship with event types
        $found_event_types = $found_selection_plan->getEventTypes()->toArray();
        $found_event_type = null;
        foreach ($found_event_types as $et) {
            if ($et->getType() === $event_type->getType()) {
                $found_event_type = $et;
                break;
            }
        }
        $this->assertNotNull($found_event_type);

        // Test ManyToMany relationship with presentation action types
        $found_action_types = $found_selection_plan->getPresentationActionTypes()->toArray();
        $found_action_type = null;
        foreach ($found_action_types as $at) {
            if ($at->getLabel() === $action_type->getLabel()) {
                $found_action_type = $at;
                break;
            }
        }
        $this->assertNotNull($found_action_type);

        // Test OneToMany relationship with track chair rating types
        $found_rating_types = $found_selection_plan->getTrackChairRatingTypes()->toArray();
        $found_rating_type = null;
        foreach ($found_rating_types as $rt) {
            if ($rt->getName() === $rating_type->getName()) {
                $found_rating_type = $rt;
                break;
            }
        }
        $this->assertNotNull($found_rating_type);
    }

    public function testDeleteSelectionPlanChildren(){
        $selection_plan_id = self::$default_selection_plan->getId();
        $repository = self::$em->getRepository(SelectionPlan::class);
        $selection_plan = $repository->find($selection_plan_id);

        // Get the current counts
        $track_groups = $selection_plan->getCategoryGroups()->toArray();
        $previous_track_groups_count = count($track_groups);

        $event_types = $selection_plan->getEventTypes()->toArray();
        $previous_event_types_count = count($event_types);

        $action_types = $selection_plan->getPresentationActionTypes()->toArray();
        $previous_action_types_count = count($action_types);

        $rating_types = $selection_plan->getTrackChairRatingTypes()->toArray();
        $previous_rating_types_count = count($rating_types);

        // Remove a track group if there are any
        if ($previous_track_groups_count > 0) {
            $selection_plan->removeTrackGroup($track_groups[0]);
        }

        // Remove an event type if there are any
        if ($previous_event_types_count > 0) {
            $selection_plan->removeEventType($event_types[0]);
        }

        // Remove a presentation action type if there are any
        if ($previous_action_types_count > 0) {
            $selection_plan->removePresentationActionType($action_types[0]);
        }

        // Remove a track chair rating type if there are any
        if ($previous_rating_types_count > 0) {
            $selection_plan->removeTrackChairRatingType($rating_types[0]);
        }

        // Clear extra questions
        $selection_plan->getExtraQuestions()->clear();

        self::$em->flush();
        self::$em->clear();

        // Retrieve the selection plan from the database
        $found_selection_plan = $repository->find($selection_plan->getId());

        // Test ManyToMany relationship with track groups
        if ($previous_track_groups_count > 0) {
            $this->assertCount($previous_track_groups_count - 1, $found_selection_plan->getCategoryGroups()->toArray());
        }

        // Test ManyToMany relationship with event types
        if ($previous_event_types_count > 0) {
            $this->assertCount($previous_event_types_count - 1, $found_selection_plan->getEventTypes()->toArray());
        }

        // Test ManyToMany relationship with presentation action types
        if ($previous_action_types_count > 0) {
            $this->assertCount($previous_action_types_count - 1, $found_selection_plan->getPresentationActionTypes()->toArray());
        }

        // Test OneToMany relationship with track chair rating types
        if ($previous_rating_types_count > 0) {
            $this->assertCount($previous_rating_types_count - 1, $found_selection_plan->getTrackChairRatingTypes()->toArray());
        }

    }
}