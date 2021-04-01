<?php namespace Tests;
use App\Models\Foundation\Main\IGroup;
use models\summit\PresentationActionType;

/**
 * Copyright 2021 OpenStack Foundation
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
use ProtectedApiTest;


/**
 * Class OAuth2SummitPresentationActionApiTest
 * @package Tests
 */
final class OAuth2SummitPresentationActionApiTest extends ProtectedApiTest
{
    use \InsertSummitTestData;

    use \InsertMemberTestData;

    static $action1 = null;
    static $action2 = null;

    protected function setUp()
    {
        $this->setCurrentGroup(IGroup::TrackChairs);
        parent::setUp();
        self::insertTestData();
        self::$summit_permission_group->addMember(self::$member);
        self::$em->persist(self::$summit);
        self::$em->persist(self::$summit_permission_group);
        self::$em->flush();
        $track_chair = self::$summit->addTrackChair(self::$member, [ self::$defaultTrack ] );

        self::$action1 = new PresentationActionType();
        self::$action1->setLabel("ACTION1");
        self::$action1->setOrder(1);
        self::$summit->addPresentationActionType(self::$action1);

        self::$action2 = new PresentationActionType();
        self::$action2->setLabel("ACTION2");
        self::$action2->setOrder(2);
        self::$summit->addPresentationActionType(self::$action2);

        self::$summit->synchAllPresentationActions();

        self::$em->persist(self::$summit);
        self::$em->flush();
    }

    protected function tearDown()
    {
        self::clearTestData();
        parent::tearDown();
    }

    public function testCompleteAction(){
        $params = [
            'summit_id' => self::$summit->getId(),
            'selection_plan_id' => self::$default_selection_plan->getId(),
            'presentation_id' => self::$presentations[0]->getId(),
            'action_id' =>  self::$presentations[0]->getPresentationActions()[0]->getId(),
            'expand' => 'type,presentation,created_by,updated_by'
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "PUT",
            "OAuth2SummitPresentationActionApiController@complete",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $action = json_decode($content);
        $this->assertTrue(!is_null($action));
    }
}