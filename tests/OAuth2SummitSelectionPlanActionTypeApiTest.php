<?php namespace Tests;
/**
 * Copyright 2022 OpenStack Foundation
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
use models\summit\PresentationActionType;
use models\summit\SelectionPlanActionType;

/**
 * Class OAuth2SummitSelectionPlanActionTypeApiTest
 * @package Tests
 */
final class OAuth2SummitSelectionPlanActionTypeApiTest extends ProtectedApiTest
{
    use InsertSummitTestData;

    use InsertMemberTestData;

    static $action1 = null;
    static $action2 = null;

    protected function setUp():void
    {
        $this->setCurrentGroup(IGroup::TrackChairs);
        parent::setUp();
        self::insertTestData();
        self::$summit_permission_group->addMember(self::$member);
        self::$em->persist(self::$summit);
        self::$em->persist(self::$summit_permission_group);
        self::$em->flush();
        self::$summit->addTrackChair(self::$member, [ self::$defaultTrack ] );

        self::$action1 = new SelectionPlanActionType();
        self::$action1->setLabel("SELECTION_PLAN_ACTION_TYPE");
        self::$action1->setOrder(1);
        self::$default_selection_plan->addSelectionPlanActionType(self::$action1);

        self::$action2 = new PresentationActionType();
        self::$action2->setLabel("PRESENTATION_ACTION_TYPE");
        self::$action2->setOrder(1);
        self::$summit->addPresentationActionType(self::$action2);

        self::$em->persist(self::$default_selection_plan);
        self::$em->persist(self::$summit);
        self::$em->flush();
    }

    protected function tearDown():void
    {
        self::clearTestData();
        parent::tearDown();
    }

    public function testGetAllBySelectionPlan(){
        $params = [
            'id'                => self::$summit->getId(),
            'selection_plan_id' => self::$default_selection_plan->getId(),
            'page'              => 1,
            'per_page'          => 10,
            'order'             => '+order',
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"       => "application/json"
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitSelectionPlanActionTypeApiController@getAllBySelectionPlan",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $page = json_decode($content);
        $this->assertTrue(!is_null($page));
        $this->assertTrue($page->total == 1);
    }

    public function testGetAllBySelectionPlanWithFiltering(){
        $params = [
            'id'                => self::$summit->getId(),
            'selection_plan_id' => self::$default_selection_plan->getId(),
            'filter'            => 'label==SELECTION_PLAN_ACTION_TYPE',
            'page'              => 1,
            'per_page'          => 10,
            'order'             => '+order',
            'expand'            => 'selection_plan'
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"       => "application/json"
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitSelectionPlanActionTypeApiController@getAllBySelectionPlan",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $page = json_decode($content);
        $this->assertTrue(!is_null($page));
        $this->assertTrue($page->total == 1);
    }

    public function testGetActionTypeById(){
         $params = [
            'id'                => self::$summit->getId(),
            'selection_plan_id' => self::$default_selection_plan->getId(),
            'action_id'         => self::$action1->getId(),
         ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"       => "application/json"
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitSelectionPlanActionTypeApiController@get",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $action = json_decode($content);
        $this->assertTrue(!is_null($action));
        $this->assertTrue($action->id == self::$action1->getId());
    }

    public function testReorderAction(){
        $params = [
            'id'                => self::$summit->getId(),
            'selection_plan_id' => self::$default_selection_plan->getId(),
            'action_id'         => self::$action2->getId(),
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"       => "application/json"
        ];

        $payload = [
            'order' => 1,
        ];

        $response = $this->action(
            "PUT",
            "OAuth2SummitSelectionPlanActionTypeApiController@update",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($payload)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $action = json_decode($content);
        $this->assertTrue(!is_null($action));
        $this->assertTrue($action->id == self::$action2->getId());
        $this->assertTrue($action->order == 1);
    }

    public function testAddAction(){
        $params = [
            'id'                => self::$summit->getId(),
            'selection_plan_id' => self::$default_selection_plan->getId(),
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"       => "application/json"
        ];

        $payload = [
            'label' => "ACTION3",
        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitSelectionPlanActionTypeApiController@add",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($payload)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $action = json_decode($content);
        $this->assertTrue(!is_null($action));
        $this->assertTrue($action->label == "ACTION3");
        $this->assertTrue($action->order == 3);
    }


    public function testUpdateAction(){
        $params = [
            'id'                => self::$summit->getId(),
            'selection_plan_id' => self::$default_selection_plan->getId(),
            'action_id'         => self::$action2->getId(),
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"       => "application/json"
        ];

        $payload = [
            'label' => self::$action2->getLabel()." UPDATE",
        ];

        $response = $this->action(
            "PUT",
            "OAuth2SummitSelectionPlanActionTypeApiController@update",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($payload)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $action = json_decode($content);
        $this->assertTrue(!is_null($action));
        $this->assertTrue($action->id == self::$action2->getId());
        $this->assertTrue($action->label == self::$action2->getLabel());
    }


    public function testDeleteAction(){
        $params = [
            'id'                => self::$summit->getId(),
            'selection_plan_id' => self::$default_selection_plan->getId(),
            'action_id'         => self::$action2->getId(),
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"       => "application/json"
        ];

        $response = $this->action(
            "DELETE",
            "OAuth2SummitSelectionPlanActionTypeApiController@delete",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(204);
        $this->assertEmpty($content);
    }
}