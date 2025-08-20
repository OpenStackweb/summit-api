<?php namespace Tests;
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
use App\Events\RSVP\RSVPCreated;
use Illuminate\Support\Facades\Event;
use Mockery;
use models\summit\RSVP;
use models\summit\SummitEvent;

/**
 * Class OAuth2RSVPApiTest
 */
final class OAuth2RSVPApiTest extends ProtectedApiTestCase
{
    use InsertSummitTestData;

    protected function setUp(): void
    {
        parent::setUp();
        self::$defaultMember = self::$member;
        self::$defaultMember2 = self::$member2;
        // seed summit, events, members, etc.
        self::insertSummitTestData();
    }

    public function tearDown(): void
    {
        Mockery::close();
        self::clearSummitTestData();
        parent::tearDown();
    }


    public function testDoRSVP201()
    {
        Event::fake();

        $summit = self::$summit;

        $event = self::$presentations[0];

        $event->setRSVPType(SummitEvent::RSVPType_Public);
        $event->setRSVPMaxUserNumber(10);
        $event->setRSVPMaxUserWaitListNumber(2);

        self::$em->persist($event);

        self::$em->flush();

        $params = [
            'id' => $summit->getId(),
            'event_id' => $event->getId(),
        ];

        $payload = [
            // no 'event_uri' nor 'answers' to keep it simple
        ];

        $response = $this->action(
            'POST',
            'OAuth2RSVPApiController@rsvp',
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders(),
            json_encode($payload)
        );

        $this->assertResponseStatus(201);

        $this->assertNotEmpty($response->getContent()); // serialized RSVP
        $json_rsvp = json_decode($response->getContent(), false);
        $this->assertNotEmpty($json_rsvp->seat_type, RSVP::SeatTypeRegular);
        $this->assertNotEmpty($json_rsvp->event_id, $event->getId());
        $this->assertNotEmpty($json_rsvp->action_date,);
        $this->assertNotEmpty($json_rsvp->owner_id, self::$member->getId());
        Event::assertDispatched(RSVPCreated::class);
    }

    public function testDoPrivateRSVP412()
    {

        $summit = self::$summit;
        $event = self::$presentations[0];

        $event->setRSVPType(SummitEvent::RSVPType_Private);
        $event->setRSVPMaxUserNumber(10);
        $event->setRSVPMaxUserWaitListNumber(2);

        self::$em->persist($event);

        self::$em->flush();

        $params = [
            'id' => $summit->getId(),
            'event_id' => $event->getId(),
        ];

        $payload = [
            // no 'event_uri' nor 'answers' to keep it simple
        ];

        $response = $this->action(
            'POST',
            'OAuth2RSVPApiController@rsvp',
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders(),
            json_encode($payload)
        );

        $this->assertResponseStatus(412);
        $json_error = json_decode($response->getContent(), false);
        $this->assertEquals("Attendee does not have invitation for this Private RSVP activity.", $json_error->errors[0]);
    }

    public function testDoRSVP404Summit()
    {
        $nonExistentSummitId = PHP_INT_MAX;
        $params = [
            'id' => $nonExistentSummitId,
            'event_id' => 12345,
        ];

        $this->action(
            'POST',
            'OAuth2RSVPApiController@rsvp',
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders(),
            json_encode([])
        );

        $this->assertResponseStatus(404);
    }

    public function testDoRSVP404Event()
    {
        $summit = self::$summit;

        // pick an event id not belonging to this summit
        $nonExistentEventId = 999999;

        $params = [
            'id' => $summit->getId(),
            'event_id' => $nonExistentEventId,
        ];

        $this->action(
            'POST',
            'OAuth2RSVPApiController@rsvp',
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders(),
            json_encode([])
        );

        $this->assertResponseStatus(404);
    }

    // -------- unrsvp (delete my RSVP) --------

    public function testUnRSVP204()
    {
        $this->testDoRSVP201();
        $summit = self::$summit;
        $event = self::$presentations[0];


        $params = [
            'id' => $summit->getId(),
            'event_id' => $event->getId(),
        ];

        $response = $this->action(
            'DELETE',
            'OAuth2RSVPApiController@unrsvp',
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders()
        );

        $this->assertResponseStatus(204);
        $this->assertEmpty($response->getContent());
    }


    public function testUnRSVP404Summit()
    {
        $params = [
            'id' => PHP_INT_MAX,
            'event_id' => self::$presentations[0]->getId(),
        ];

        $response = $this->action(
            'DELETE',
            'OAuth2RSVPApiController@unrsvp',
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders()
        );

        $this->assertResponseStatus(404);
    }

    public function testUnRSVP404Event()
    {
        $params = [
            'id' => self::$summit->getId(),
            'event_id' => 999999,
        ];

        $response = $this->action(
            'DELETE',
            'OAuth2RSVPApiController@unrsvp',
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders()
        );

        $this->assertResponseStatus(404);
    }

    // -------- getAllByEventId (basic guards & happy path skeleton) --------

    public function testGetAllBySummitEvent404Summit()
    {
        $params = [
            'id' => PHP_INT_MAX,
            'event_id' => 12345,
        ];

        $response = $this->action(
            'GET',
            'OAuth2RSVPApiController@getAllByEventId',
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders()
        );

        $this->assertResponseStatus(404);
    }

    public function testGetAllBySummitEvent404Event()
    {
        $params = [
            'id' => self::$summit->getId(),
            'event_id' => 999999,
        ];

        $response = $this->action(
            'GET',
            'OAuth2RSVPApiController@getAllByEventId',
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders()
        );

        $this->assertResponseStatus(404);
    }

    public function testGetAllBySummitEvent200Empty()
    {
        $params = [
            'id' => self::$summit->getId(),
            'event_id' => self::$presentations[0]->getId(),
            'relations' => 'none',
            'expand' => 'none',
            'page' => 1,
            'per_page' => 5,
        ];

        $response = $this->action(
            'GET',
            'OAuth2RSVPApiController@getAllByEventId',
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders()
        );

        $this->assertResponseStatus(200);
        $payload = json_decode($response->getContent());
        $this->assertNotNull($payload);
        $this->assertTrue(isset($payload->total));
        $this->assertTrue(isset($payload->current_page));
        $this->assertTrue(isset($payload->per_page));
        $this->assertTrue(isset($payload->data));
        $this->assertEmpty($payload->data);
    }
}
