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
use models\summit\SummitOrderExtraQuestionTypeConstants;
/**
 * Class OAuth2SummitSelectionPlanExtraQuestionTypeApiTest
 */
final class OAuth2SummitSelectionPlansApiTest extends ProtectedApiTest
{

    use InsertSummitTestData;

    use InsertMemberTestData;

    protected function setUp():void
    {
        parent::setUp();
        self::insertTestData();
        self::insertMemberTestData(IGroup::TrackChairs);
        self::$summit_permission_group->addMember(self::$member);
        self::$em->persist(self::$summit);
        self::$em->persist(self::$summit_permission_group);
        self::$em->flush();
    }

    protected function tearDown():void
    {
        self::clearMemberTestData();
        self::clearTestData();
        parent::tearDown();
    }

    public function testGetSelectionPlan(){

        $params = [
            'id'                => self::$summit->getId(),
            'selection_plan_id' => self::$default_selection_plan->getId(),
            'expand'            => 'track_groups,extra_questions,extra_questions.values,emails'
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"       => "application/json"
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitSelectionPlansApiController@getSelectionPlan",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $selectionPlan = json_decode($content);
        $this->assertTrue(!is_null($selectionPlan));

        return $selectionPlan;
    }

    public function testAddSelectionPlan(){
        $params = [
            'id' => self::$summit->getId(),
        ];

        $data = [
            'name' => 'my_selecion_plan',
            'is_enabled' => true,
            'allow_new_presentations' => true,
            'max_submission_allowed_per_user' => 1,
            'submission_begin_date' => 1649108093,
            'submission_end_date'   => 1649109093,
            'presentation_creator_notification_email_template'      => 'creator_email_template',
            'presentation_moderator_notification_email_template'    => 'moderator_email_template',
            'presentation_speaker_notification_email_template'      => 'speaker_email_template',
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"       => "application/json"
        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitSelectionPlansApiController@addSelectionPlan",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $selectionPlan = json_decode($content);
        $this->assertTrue(!is_null($selectionPlan));
    }

    public function testUpdateSelectionPlan(){
        $params = [
            'id'                => self::$summit->getId(),
            'selection_plan_id' => self::$default_selection_plan->getId(),
        ];

        $data = [
            'presentation_creator_notification_email_template'      => 'creator_email_template',
            'presentation_moderator_notification_email_template'    => '',
            'presentation_speaker_notification_email_template'      => 'speaker_email_template',
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"       => "application/json"
        ];

        $response = $this->action(
            "PUT",
            "OAuth2SummitSelectionPlansApiController@updateSelectionPlan",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $selectionPlan = json_decode($content);
        $this->assertTrue(!is_null($selectionPlan));
    }

    public function testAttachEventType(){
        $params = [
            'id'                => self::$summit->getId(),
            'selection_plan_id' => self::$default_selection_plan->getId(),
            'event_type_id'     => self::$defaultEventType->getId(),
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"       => "application/json"
        ];

        $response = $this->action(
            "PUT",
            "OAuth2SummitSelectionPlansApiController@attachEventType",
            $params,
            [],
            [],
            [],
            $headers
        );

        $this->assertResponseStatus(201);
        $this->assertTrue(self::$default_selection_plan->getEventTypes()->count() >= 1);
    }

    public function testDetachEventType(){
        $params = [
            'id'                => self::$summit->getId(),
            'selection_plan_id' => self::$default_selection_plan->getId(),
            'event_type_id'     => self::$defaultEventType->getId(),
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"       => "application/json"
        ];

        $this->action(
            "PUT",
            "OAuth2SummitSelectionPlansApiController@attachEventType",
            $params,
            [],
            [],
            [],
            $headers
        );

        $this->action(
            "DELETE",
            "OAuth2SummitSelectionPlansApiController@detachEventType",
            $params,
            [],
            [],
            [],
            $headers
        );

        $this->assertResponseStatus(204);
    }
}