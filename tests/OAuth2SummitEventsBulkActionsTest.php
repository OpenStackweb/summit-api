<?php namespace Tests;
/**
 * Copyright 2017 OpenStack Foundation
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

class OAuth2SummitEventsBulkActionsTest extends ProtectedApiTestCase
{
    use InsertSummitTestData;

    protected function setUp(): void
    {
        $this->setCurrentGroup(IGroup::TrackChairs);
        parent::setUp();
        self::$defaultMember = self::$member;
        self::insertSummitTestData();
    }

    protected function tearDown(): void
    {
        self::clearSummitTestData();
        parent::tearDown();
    }

    public function testUpdateEvents()
    {
        $events = self::$summit->getEvents();
        $this->assertGreaterThanOrEqual(2, $events->count());

        $event1 = $events->first();
        $event2 = $events->next();

        $params = [
            'id' => self::$summit->getId(),
        ];

        $data = [
           'events' => [
               [
                   'id' => $event1->getId(),
                   'title' => 'Bulk Updated Title 1'
               ],
               [
                   'id' => $event2->getId(),
                   'title' => 'Bulk Updated Title 2'
               ]
           ]
        ];

        $response = $this->action(
            "PUT",
            "OAuth2SummitEventsApiController@updateEvents",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders(),
            json_encode($data)
        );

        $this->assertResponseStatus(201);
    }

    public function testGetEventByIdOR()
    {
        $events = self::$summit->getEvents();
        $this->assertGreaterThanOrEqual(2, $events->count());

        $event1 = $events->first();
        $event2 = $events->next();

        $params = [
            'id' => self::$summit->getId(),
            'filter' => [
                sprintf('id==%s,id==%s', $event1->getId(), $event2->getId()),
            ]
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitEventsApiController@getEvents",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders()
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);

        $events_response = json_decode($content);
        $this->assertNotNull($events_response);
        $this->assertGreaterThanOrEqual(1, $events_response->total);
    }
}
