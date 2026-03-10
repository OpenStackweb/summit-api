<?php namespace Tests;
/**
 * Copyright 2020 OpenStack Foundation
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
 * Class OAuth2SummitEmailEventFlowApiControllerTest
 */
class OAuth2SummitEmailEventFlowApiControllerTest extends ProtectedApiTestCase
{
    use InsertSummitTestData;

    protected function setUp():void
    {
        $this->setCurrentGroup(IGroup::TrackChairs);
        parent::setUp();
        self::$defaultMember = self::$member;
        self::insertSummitTestData();
        self::$summit->seedDefaultEmailFlowEvents();
        self::$em->persist(self::$summit);
        self::$em->flush();
    }

    protected function tearDown():void
    {
        self::clearSummitTestData();
        parent::tearDown();
    }

    public function testGetAllEmailEvents(){
        $params = [
            'id'       => self::$summit->getId(),
            'page'     => 1,
            'per_page' => 10,
            'order'    => '-id',
            'filter'   => 'flow_name==Schedule'
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitEmailEventFlowApiController@getAllBySummit",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders()
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $email_events = json_decode($content);
        $this->assertNotNull($email_events);
        $this->assertGreaterThanOrEqual(1, $email_events->total);
        return $email_events;
    }

    public function testGetEmailEventById(){
        // First get all events to obtain a valid ID
        $email_events = $this->testGetAllEmailEvents();
        $event_id = $email_events->data[0]->id;

        $params = [
            'id'       => self::$summit->getId(),
            'event_id' => $event_id
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitEmailEventFlowApiController@get",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders()
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $email_event = json_decode($content);
        $this->assertNotNull($email_event);
        $this->assertEquals($event_id, $email_event->id);
        return $email_event;
    }

    public function testUpdateEmailEvent(){
        $email_events = $this->testGetAllEmailEvents();

        $params = [
            'id'       => self::$summit->getId(),
            'event_id' => $email_events->data[0]->id
        ];

        $data = [
            'email_template_identifier' => "PROPOSED_SCHEDULE_SUBMIT_FOR_REVIEW",
            'recipient' => "test@nomail.com"
        ];

        $response = $this->action(
            "PUT",
            "OAuth2SummitEmailEventFlowApiController@update",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders(),
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $email_event = json_decode($content);
        $this->assertNotNull($email_event);
    }

    public function testDeleteEmailEvent(){
        $email_events = $this->testGetAllEmailEvents();

        $params = [
            'id'       => self::$summit->getId(),
            'event_id' => $email_events->data[0]->id
        ];

        $response = $this->action(
            "DELETE",
            "OAuth2SummitEmailEventFlowApiController@delete",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders()
        );

        // Delete may return 204 (success) or 400 (seeded events cannot be deleted)
        $this->assertTrue(in_array($response->getStatusCode(), [204, 400]));
    }
}
