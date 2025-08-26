<?php namespace Tests;
/**
 * Copyright 2024 OpenStack Foundation
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
use Illuminate\Support\Facades\App;
use Mockery;
use models\main\Member;
use models\summit\RSVP;
use models\summit\Summit;
use models\summit\SummitEvent;
use App\Services\Model\ISummitRSVPService;
use models\oauth2\IResourceServerContext;

/**
 * Class OAuth2RSVPApiWithMocksTest
 */
final class OAuth2RSVPApiWithMocksTest extends ProtectedApiTestCase
{
    use InsertSummitTestData;

    /** @var ISummitRSVPService|Mockery\MockInterface */
    private $rsvp_service_mock;

    /** @var IResourceServerContext|Mockery\MockInterface */
    private $resource_server_context_mock;

    protected function setUp(): void
    {
        parent::setUp();

        // seed summit, events, members, etc.
        self::insertSummitTestData();

        // default: authenticated as self::$member in the access token (like other tests)
        // BUT the controller also calls resource_server_context->getCurrentUser(),
        // so we bind a mock that returns the same member by default.
        $this->resource_server_context_mock = Mockery::mock(IResourceServerContext::class)->makePartial();
        $this->resource_server_context_mock->shouldReceive('getCurrentUser')
            ->byDefault()
            ->andReturn(self::$member);
        $this->resource_server_context_mock->shouldReceive('getCurrentClient')
            ->byDefault()
            ->andReturn(null);
        $this->resource_server_context_mock->shouldReceive('getCurrentScope')
            ->byDefault()
            ->andReturn([]);
        $this->resource_server_context_mock->shouldReceive('setAuthorizationContext');

        App::instance(IResourceServerContext::class, $this->resource_server_context_mock);

        // RSVP service mock (we keep repositories real from fixtures)
        $this->rsvp_service_mock = Mockery::mock(ISummitRSVPService::class)->shouldIgnoreMissing(false);
        App::instance(ISummitRSVPService::class, $this->rsvp_service_mock);
    }

    public function tearDown(): void
    {
        Mockery::close();
        self::clearSummitTestData();
        parent::tearDown();
    }

    // -------- rsvo (create my RSVP) --------

    public function testDoRSVP201()
    {

        $summit = self::$summit;
        $event  = self::$presentations[0];
        $event->setRSVPType(SummitEvent::RSVPType_Public);
        $event->setRSVPMaxUserNumber(10);
        $event->setRSVPMaxUserWaitListNumber(2);
        // Minimal RSVP entity for serialization
        $rsvp = new RSVP;
        $rsvp->setOwner(self::$member);
        $rsvp->setEvent($event);

        $this->rsvp_service_mock
            ->shouldReceive('rsvpEvent')
            ->once()
            ->withArgs(function ($s, $member, $eventId, $payload) use ($summit, $event) {
                $this->assertSame($summit->getId(), $s->getId());
                $this->assertInstanceOf(Member::class, $member);
                $this->assertEquals($event->getId(), (int)$eventId);
                $this->assertNull($payload['event_uri']); // we’re not sending event_uri on this test
                return true;
            })
            ->andReturn($rsvp);

        $params = [
            'id'       => $summit->getId(),
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
    }


    public function testDoRSVP403WhenNoCurrentUser()
    {
        // Make resource context return null member
        $this->resource_server_context_mock->shouldReceive('getCurrentUser')->andReturn(null);

        $params = [
            'id'       => self::$summit->getId(),
            'event_id' => self::$presentations[0]->getId(),
        ];

        $response = $this->action(
            'POST',
            'OAuth2RSVPApiController@rsvp',
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders(), // token is present, but controller checks context user explicitly
            json_encode([])
        );

        $this->assertResponseStatus(403);
        // service must not be called
        $this->rsvp_service_mock->shouldNotHaveReceived('rsvpEvent');
    }

    /** *********************************************************************
     * getById (ADMIN)
     ********************************************************************* */

    public function testGetRSVPById200(): void
    {
        $summit = self::$summit;
        $event  = self::$presentations[0];

        // Create an RSVP on this event with a fixed ID
        $rsvp = new RSVP;
        $rsvp->setOwner(self::$member);
        $rsvp->setEvent($event);

        // Set RSVP ID via reflection (so event->getRSVPById() can find it)
        $rprop = new \ReflectionProperty(\models\summit\RSVP::class, 'id');
        $rprop->setAccessible(true);
        $rprop->setValue($rsvp, 777);

        // Ensure RSVP collection on event contains our RSVP
        $eprop = new \ReflectionProperty(\models\summit\SummitEvent::class, 'rsvp');
        $eprop->setAccessible(true);
        $collection = $eprop->getValue($event);
        if (!$collection instanceof \Doctrine\Common\Collections\ArrayCollection) {
            $collection = new \Doctrine\Common\Collections\ArrayCollection();
        }
        $collection->add($rsvp);
        $eprop->setValue($event, $collection);

        $params = [
            'id'       => $summit->getId(),
            'event_id' => $event->getId(),
            'rsvp_id'  => 777,
        ];

        $response = $this->action(
            'GET',
            'OAuth2RSVPApiController@getById',
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders()
        );

        $this->assertResponseStatus(200);
        $this->assertNotEmpty($response->getContent());
        $json = json_decode($response->getContent(), true);
        $this->assertEquals(777, $json['id'] ?? null);
    }

    public function testGetRSVPById403WhenNotFound(): void
    {
        $params = [
            'id'       => self::$summit->getId(),
            'event_id' => self::$presentations[0]->getId(),
            'rsvp_id'  => 999999, // does not exist
        ];

        $this->action(
            'GET',
            'OAuth2RSVPApiController@getById',
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders()
        );

        $this->assertResponseStatus(403); // controller throws HTTP403ForbiddenException when not found
    }

    /** *********************************************************************
     * delete (ADMIN)
     ********************************************************************* */

    public function testDeleteRSVP204(): void
    {
        $summit = self::$summit;
        $event  = self::$presentations[0];

        // Seed an RSVP on the event
        $rsvp = new RSVP;
        $rsvp->setOwner(self::$member);
        $rsvp->setEvent($event);

        $rprop = new \ReflectionProperty(\models\summit\RSVP::class, 'id');
        $rprop->setAccessible(true);
        $rprop->setValue($rsvp, 555);

        $eprop = new \ReflectionProperty(\models\summit\SummitEvent::class, 'rsvp');
        $eprop->setAccessible(true);
        $collection = $eprop->getValue($event);
        if (!$collection instanceof \Doctrine\Common\Collections\ArrayCollection) {
            $collection = new \Doctrine\Common\Collections\ArrayCollection();
        }
        $collection->add($rsvp);
        $eprop->setValue($event, $collection);

        $params = [
            'id'       => $summit->getId(),
            'event_id' => $event->getId(),
            'rsvp_id'  => 555,
        ];

        $this->action(
            'DELETE',
            'OAuth2RSVPApiController@delete',
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders()
        );

        $this->assertResponseStatus(204);

    }

    public function testDeleteRSVP403WhenNotFound(): void
    {
        $params = [
            'id'       => self::$summit->getId(),
            'event_id' => self::$presentations[0]->getId(),
            'rsvp_id'  => 424242, // not present
        ];

        $this->action(
            'DELETE',
            'OAuth2RSVPApiController@delete',
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders()
        );

        $this->assertResponseStatus(403); // getRSVPOr404 throws HTTP403ForbiddenException
    }

    /** *********************************************************************
     * update (ADMIN)
     ********************************************************************* */

    public function testUpdateRSVP201(): void
    {
        $summit = self::$summit;
        $event  = self::$presentations[0];

        // Minimal updated RSVP for serializer
        $updated = new RSVP;
        $updated->setOwner(self::$member);
        $updated->setEvent($event);

        $payload = [
            'seat_type' => RSVP::SeatTypeWaitList,
            'status'    => RSVP::Status_Active,
            'answers'   => [], // keep simple
        ];

        $this->rsvp_service_mock
            ->shouldReceive('update')
            ->once()
            ->withArgs(function ($eventArg, $rsvpIdArg, $payloadArg) use ($event, $payload) {
                $this->assertInstanceOf(SummitEvent::class, $eventArg);
                $this->assertEquals($event->getId(), $eventArg->getId());
                $this->assertEquals(1234, (int)$rsvpIdArg);
                $this->assertEquals($payload, $payloadArg);
                return true;
            })
            ->andReturn($updated);

        $params = [
            'id'       => $summit->getId(),
            'event_id' => $event->getId(),
            'rsvp_id'  => 1234,
        ];

        $response = $this->action(
            'PUT',
            'OAuth2RSVPApiController@update',
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders(),
            json_encode($payload)
        );

        $this->assertResponseStatus(201);
        $this->assertNotEmpty($response->getContent());
    }

    public function testUpdateRSVP400ValidationErrorFromPayload(): void
    {
        // seat_type invalid -> controller validation should fail before hitting service
        $params = [
            'id'       => self::$summit->getId(),
            'event_id' => self::$presentations[0]->getId(),
            'rsvp_id'  => 999,
        ];

        $payload = [
            'seat_type' => 'NOT_A_VALID_SEAT_TYPE',
        ];

        $response = $this->action(
            'PUT',
            'OAuth2RSVPApiController@update',
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders(),
            json_encode($payload)
        );

        // Precondition failed (422) or 412 depending on your base controller; use what your stack returns
        $this->assertTrue(in_array($response->getStatusCode(), [412, 422], true));
        // service must not be called
        $this->rsvp_service_mock->shouldNotHaveReceived('update');
    }

}
