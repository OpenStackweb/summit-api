<?php namespace Tests;
/**
 * Copyright 2019 OpenStack Foundation
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

use App\Models\Foundation\Main\IGroup;
use LaravelDoctrine\ORM\Facades\Registry;
use models\summit\Presentation;
use models\summit\SummitEvent;
use models\summit\SummitOrderExtraQuestionTypeConstants;
use models\utils\SilverstripeBaseModel;

/**
 * Class OAuth2SummitSelectionPlanExtraQuestionTypeApiTest
 */
final class OAuth2SummitSelectionPlansApiTest extends ProtectedApiTestCase
{

    use InsertSummitTestData;

    use InsertMemberTestData;

    protected function setUp(): void
    {
        $this->setCurrentGroup(IGroup::TrackChairs);
        parent::setUp();
        self::insertMemberTestData(IGroup::TrackChairs);
        self::insertSummitTestData();
        self::$summit_permission_group->addMember(self::$member);
        self::$em->persist(self::$summit);
        self::$em->persist(self::$summit_permission_group);
        self::$em->flush();
        self::$summit->addTrackChair(self::$member, [self::$defaultTrack]);
        self::$em->persist(self::$summit);
        self::$em->flush();
    }

    protected function tearDown(): void
    {
        self::clearMemberTestData();
        self::clearSummitTestData();
        parent::tearDown();
    }

    private function getHeaders()
    {
        return [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        ];
    }

    public function testGetSelectionPlan()
    {

        $params = [
            'id' => self::$summit->getId(),
            'selection_plan_id' => self::$default_selection_plan->getId(),
            'expand' =>
                implode(',', [
                    'track_groups',
                    'extra_questions',
                    'extra_questions.values',
                    'track_chair_rating_types',
                    'track_chair_rating_types.score_types',
                    'allowed_presentation_action_types'
                ])
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitSelectionPlansApiController@getSelectionPlan",
            $params,
            [],
            [],
            [],
            $this->getHeaders()
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $selectionPlan = json_decode($content);
        $this->assertTrue(!is_null($selectionPlan));
        $this->assertTrue($selectionPlan->id > 0);
        $this->assertTrue(count($selectionPlan->track_chair_rating_types) > 0);
        $this->assertTrue(count($selectionPlan->track_chair_rating_types[0]->score_types) > 0);
        return $selectionPlan;
    }

    public function testAddSelectionPlan()
    {
        $params = [
            'id' => self::$summit->getId(),
        ];

        $data = [
            'name' => 'my_selecion_plan',
            'is_enabled' => true,
            'is_hidden' => false,
            'allow_new_presentations' => true,
            'max_submission_allowed_per_user' => 1,
            'submission_begin_date' => 1649108093,
            'submission_end_date' => 1649109093,
            'presentation_creator_notification_email_template' => 'creator_email_template',
            'presentation_moderator_notification_email_template' => 'moderator_email_template',
            'presentation_speaker_notification_email_template' => 'speaker_email_template',

        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitSelectionPlansApiController@addSelectionPlan",
            $params,
            [],
            [],
            [],
            $this->getHeaders(),
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $selectionPlan = json_decode($content);
        $this->assertTrue(!is_null($selectionPlan));
        $this->assertNotEmpty($selectionPlan->allowed_presentation_questions);
    }

    public function testUpdateSelectionPlan()
    {
        $is_hidden = true;

        $params = [
            'id' => self::$summit->getId(),
            'selection_plan_id' => self::$default_selection_plan->getId(),
        ];

        $data = [
            'presentation_creator_notification_email_template' => 'creator_email_template',
            'presentation_moderator_notification_email_template' => '',
            'presentation_speaker_notification_email_template' => 'speaker_email_template',
            //'allowed_presentation_questions' => [SummitEvent::FieldLevel, SummitEvent::FieldTitle, Presentation::FieldWillAllSpeakersAttend],
            'allow_track_change_requests' => true,
            'is_hidden' => $is_hidden
        ];

        $response = $this->action(
            "PUT",
            "OAuth2SummitSelectionPlansApiController@updateSelectionPlan",
            $params,
            [],
            [],
            [],
            $this->getHeaders(),
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $selectionPlan = json_decode($content);
        $this->assertTrue(!is_null($selectionPlan));
        $this->assertNotEmpty($selectionPlan->allowed_presentation_questions);
        $this->assertCount(6, $selectionPlan->allowed_presentation_questions);
        $this->assertEquals($is_hidden, $selectionPlan->is_hidden);
    }

    public function testAttachPresentationType()
    {
        $params = [
            'id' => self::$summit->getId(),
            'selection_plan_id' => self::$default_selection_plan->getId(),
            'event_type_id' => self::$defaultPresentationType->getId(),
        ];

        $this->action(
            "PUT",
            "OAuth2SummitSelectionPlansApiController@attachEventType",
            $params,
            [],
            [],
            [],
            $this->getHeaders()
        );

        $this->assertResponseStatus(201);
        $this->assertTrue(self::$default_selection_plan->getEventTypes()->count() >= 1);
    }

    public function testAttachNonPresentationEventType()
    {
        $params = [
            'id' => self::$summit->getId(),
            'selection_plan_id' => self::$default_selection_plan->getId(),
            'event_type_id' => self::$defaultEventType->getId(),
        ];

        $this->action(
            "PUT",
            "OAuth2SummitSelectionPlansApiController@attachEventType",
            $params,
            [],
            [],
            [],
            $this->getHeaders()
        );

        $this->assertResponseStatus(412);
    }

    public function testDetachEventType()
    {
        $params = [
            'id' => self::$summit->getId(),
            'selection_plan_id' => self::$default_selection_plan->getId(),
            'event_type_id' => self::$defaultEventType->getId(),
        ];

        $this->action(
            "PUT",
            "OAuth2SummitSelectionPlansApiController@attachEventType",
            $params,
            [],
            [],
            [],
            $this->getHeaders()
        );

        $this->action(
            "DELETE",
            "OAuth2SummitSelectionPlansApiController@detachEventType",
            $params,
            [],
            [],
            [],
            $this->getHeaders()
        );

        $this->assertResponseStatus(204);
    }

    public function testGetPresentationWithRatingTypes()
    {

        $params = [
            'id' => self::$summit->getId(),
            'selection_plan_id' => self::$default_selection_plan->getId(),
            'page' => 1,
            'per_page' => 10,
            'expand' => 'track_chair_scores,track_chair_scores.type,track_chair_scores.type.type'
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitSelectionPlansApiController@getSelectionPlanPresentations",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $presentations = json_decode($content);
        $this->assertTrue(!is_null($presentations));
    }

    public function testAddAllowedMember()
    {
        $params = [
            'id' => self::$summit->getId(),
            'selection_plan_id' => self::$default_selection_plan->getId(),
        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitSelectionPlansApiController@addAllowedMember",
            $params,
            [],
            [],
            [],
            $this->getHeaders(),
            json_encode([
                'email' => self::$member->getEmail()
            ])
        );

        $this->assertResponseStatus(201);
        $content = $response->getContent();
        $this->assertTrue(self::$default_selection_plan->getAllowedMembers()->count() >= 1);
    }

    public function testAddAllowedMemberAndGet()
    {
        $params = [
            'id' => self::$summit->getId(),
            'selection_plan_id' => self::$default_selection_plan->getId(),
        ];

        $this->action(
            "POST",
            "OAuth2SummitSelectionPlansApiController@addAllowedMember",
            $params,
            [],
            [],
            [],
            $this->getHeaders(),
            json_encode([
                'email' => self::$member->getEmail()
            ])
        );

        $this->assertResponseStatus(201);
        $this->assertTrue(self::$default_selection_plan->getAllowedMembers()->count() >= 1);

        $params = [
            'id' => self::$summit->getId(),
            'expand' => 'track_groups,extra_questions,extra_questions.values'
        ];

        $response = $this->action
        (
            "GET",
            "OAuth2SummitSelectionPlansApiController@getMySelectionPlans",
            $params,
            [],
            [],
            [],
            $this->getHeaders()
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $page = json_decode($content);
        $this->assertTrue(!is_null($page));
        $this->assertNotEmpty($page->data);
    }

    public function testAddAllowedMemberDelete()
    {
        $params = [
            'id' => self::$summit->getId(),
            'selection_plan_id' => self::$default_selection_plan->getId(),
        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitSelectionPlansApiController@addAllowedMember",
            $params,
            [],
            [],
            [],
            $this->getHeaders(),
            json_encode([
                'email' => self::$member->getEmail()
            ])
        );

        $this->assertResponseStatus(201);
        $content = $response->getContent();
        $entity = json_decode($content);
        $this->assertTrue($entity->id > 1);

        $params = [
            'id' => self::$summit->getId(),
            'selection_plan_id' => self::$default_selection_plan->getId(),
            'allowed_member_id' => $entity->id,
        ];

        $this->action(
            "DELETE",
            "OAuth2SummitSelectionPlansApiController@removeAllowedMember",
            $params,
            [],
            [],
            [],
            $this->getHeaders(),
        );

        $this->assertResponseStatus(204);
    }

     public function testAddAllowedMemberWhenIsHiddenPlan()
    {
        $params = [
            'id' => self::$summit->getId(),
            'selection_plan_id' => self::$default_selection_plan->getId(),
        ];

        $data = [
            'is_hidden' => true
        ];

        $this->action(
            "PUT",
            "OAuth2SummitSelectionPlansApiController@updateSelectionPlan",
            $params,
            [],
            [],
            [],
            $this->getHeaders(),
            json_encode($data)
        );

        $this->assertResponseStatus(201);

        $response = $this->action(
            "POST",
            "OAuth2SummitSelectionPlansApiController@addAllowedMember",
            $params,
            [],
            [],
            [],
            $this->getHeaders(),
            json_encode([
                'email' => self::$member->getEmail()
            ])
        );

        $content = $response->getContent();
        $this->assertResponseStatus(412);
        $this->assertStringContainsString("because it's hidden", $content);
    }
}