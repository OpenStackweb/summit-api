<?php namespace Tests;
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
use App\Models\Foundation\Main\IGroup;
/**
 * Class OAuth2SummitPresentationActionApiTest
 * @package Tests
 */
final class OAuth2SummitPresentationActionApiTest extends ProtectedApiTestCase
{
    use InsertSummitTestData;

    protected function setUp():void
    {
        $this->setCurrentGroup(IGroup::TrackChairs);
        parent::setUp();
        self::$defaultMember = self::$member;
        self::insertSummitTestData();
        self::$summit_permission_group->addMember(self::$member);
        self::$em->persist(self::$summit);
        self::$em->persist(self::$summit_permission_group);
        self::$em->flush();
        $track_chair = self::$summit->addTrackChair(self::$member, [ self::$defaultTrack ] );
        self::$em->persist(self::$summit);
        self::$em->flush();
    }

    protected function tearDown():void
    {
        self::clearSummitTestData();
        parent::tearDown();
    }

    public function testCompleteAction(){
        // Capture IDs before clearing EM to force fresh DB loads
        $summit_id = self::$summit->getId();
        $selection_plan_id = self::$default_selection_plan->getId();
        $presentation_id = self::$presentations[0]->getId();
        $action_type_id = self::$default_presentation_action_type->getId();

        // Clear Doctrine identity map so controller loads fresh entities from DB
        self::$em->clear();

        $params = [
            'id' => $summit_id,
            'selection_plan_id' => $selection_plan_id,
            'presentation_id' => $presentation_id,
            'action_type_id' => $action_type_id,
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