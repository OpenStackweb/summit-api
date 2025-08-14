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
use App\Jobs\Emails\Schedule\RSVP\RSVPInviteEmail;
use App\Models\Foundation\Summit\Events\RSVP\RSVPInvitation;
use App\Services\ISummitRSVPInvitationService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Storage;
use Mockery;
use models\main\Member;
use models\summit\SummitAttendee;
use models\oauth2\IResourceServerContext;

/**
 * Class OAuth2RSVPInvitationApiTest
 */
final class OAuth2RSVPInvitationApiTest extends ProtectedApiTestCase
{
    use InsertSummitTestData;

    /** @var ISummitRSVPInvitationService|Mockery\MockInterface */
    private $service_mock;

    /** @var IResourceServerContext|Mockery\MockInterface */
    private $resource_server_context_mock;

    protected function setUp(): void
    {
        parent::setUp();

        // seed summit/events/members
        self::insertSummitTestData();

        // by default, behave as authenticated member on resource ctx
        $this->resource_server_context_mock = Mockery::mock(IResourceServerContext::class);
        $this->resource_server_context_mock->shouldReceive('getCurrentUser')->byDefault()->andReturn(self::$member);
        $this->resource_server_context_mock->shouldReceive('getCurrentClient')->byDefault()->andReturn(null);
        $this->resource_server_context_mock->shouldReceive('getCurrentScope')->byDefault()->andReturn([]);
        $this->resource_server_context_mock->shouldReceive('setAuthorizationContext');
        App::instance(IResourceServerContext::class, $this->resource_server_context_mock);

        // service mock
        $this->service_mock = Mockery::mock(ISummitRSVPInvitationService::class)->shouldIgnoreMissing(false);
        App::instance(ISummitRSVPInvitationService::class, $this->service_mock);

        Storage::fake('local');
    }

    public function tearDown(): void
    {
        Mockery::close();
        self::clearSummitTestData();
        parent::tearDown();
    }

    public function testIngestInvitations200()
    {
        $summit = self::$summit;
        $event  = self::$presentations[0];

        $file = UploadedFile::fake()->create('invitations.csv', 2, 'text/csv');

        $this->service_mock
            ->shouldReceive('importInvitationData')
            ->once()
            ->withArgs(function ($evt, $uploaded) use ($event, $file) {
                $this->assertEquals($event->getId(), $evt->getId());
                $this->assertInstanceOf(UploadedFile::class, $uploaded);
                $this->assertEquals($file->getClientOriginalName(), $uploaded->getClientOriginalName());
                return true;
            });

        $params = [
            'id'       => $summit->getId(),
            'event_id' => $event->getId(),
        ];

        $response = $this->action(
            'PUT',
            'OAuth2RSVPInvitationApiController@ingestInvitations',
            $params,
            [], // params
            [], // cookies
            ['file' => $file], // files
            $this->getAuthHeaders()
        );

        $this->assertResponseStatus(200);
    }

    public function testIngestInvitations412WhenFileMissing()
    {
        $params = [
            'id'       => self::$summit->getId(),
            'event_id' => self::$presentations[0]->getId(),
        ];

        $response = $this->action(
            'PUT',
            'OAuth2RSVPInvitationApiController@ingestInvitations',
            $params,
            [], // post
            [], // cookies
            [], // files -> missing
            $this->getAuthHeaders()
        );

        $this->assertResponseStatus(412);
        $this->service_mock->shouldNotHaveReceived('importInvitationData');
    }

    public function testIngestInvitations403WhenNoCurrentUser()
    {
        $this->resource_server_context_mock->shouldReceive('getCurrentUser')->andReturn(null);

        $params = [
            'id'       => self::$summit->getId(),
            'event_id' => self::$presentations[0]->getId(),
        ];

        $file = UploadedFile::fake()->create('invitations.csv', 2, 'text/csv');

        $response = $this->action(
            'PUT',
            'OAuth2RSVPInvitationApiController@ingestInvitations',
            $params,
            [],
            [],
            ['file' => $file],
            $this->getAuthHeaders()
        );

        $this->assertResponseStatus(403);
        $this->service_mock->shouldNotHaveReceived('importInvitationData');
    }

    public function testIngestInvitations404Summit()
    {
        $file = UploadedFile::fake()->create('invitations.csv', 2, 'text/csv');

        $params = [
            'id'       => PHP_INT_MAX,
            'event_id' => 12345,
        ];

        $response = $this->action(
            'PUT',
            'OAuth2RSVPInvitationApiController@ingestInvitations',
            $params,
            [],
            [],
            ['file' => $file],
            $this->getAuthHeaders()
        );

        $this->assertResponseStatus(404);
        $this->service_mock->shouldNotHaveReceived('importInvitationData');
    }

    public function testIngestInvitations404Event()
    {
        $file = UploadedFile::fake()->create('invitations.csv', 2, 'text/csv');

        $params = [
            'id'       => self::$summit->getId(),
            'event_id' => 999999,
        ];

        $response = $this->action(
            'PUT',
            'OAuth2RSVPInvitationApiController@ingestInvitations',
            $params,
            [],
            [],
            ['file' => $file],
            $this->getAuthHeaders()
        );

        $this->assertResponseStatus(404);
        $this->service_mock->shouldNotHaveReceived('importInvitationData');
    }

    // -------------------- getAllByEventId (GET) --------------------

    public function testGetAllByEventId200Envelope()
    {
        $params = [
            'id'       => self::$summit->getId(),
            'event_id' => self::$presentations[0]->getId(),
            'relations'=> 'none',
            'expand'   => 'none',
            'page'     => 1,
            'per_page' => 5,
        ];

        $response = $this->action(
            'GET',
            'OAuth2RSVPInvitationApiController@getAllByEventId',
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
    }


    // -------------------- send (PUT JSON) --------------------

    public function testSend400WhenNotJson()
    {
        $params = [
            'id'       => self::$summit->getId(),
            'event_id' => self::$presentations[0]->getId(),
        ];

        $headers = array_merge($this->getAuthHeaders(), ['CONTENT_TYPE' => 'text/plain']);

        $response = $this->action(
            'PUT',
            'OAuth2RSVPInvitationApiController@send',
            $params,
            [],
            [],
            [],
            $headers,
            null // no json body
        );

        $this->assertResponseStatus(400);
        $this->service_mock->shouldNotHaveReceived('triggerSend');
    }

    public function testSend412WhenValidationFails()
    {
        $params = [
            'id'       => self::$summit->getId(),
            'event_id' => self::$presentations[0]->getId(),
        ];

        $headers = array_merge($this->getAuthHeaders(), ['CONTENT_TYPE' => 'application/json']);

        $payload = [
            'email_flow_event' => 'invalid_event_slug'
        ];

        $response = $this->action(
            'PUT',
            'OAuth2RSVPInvitationApiController@send',
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($payload)
        );

        $this->assertResponseStatus(412);
        $this->service_mock->shouldNotHaveReceived('triggerSend');
    }

    public function testSend200TriggersService()
    {
        $summit = self::$summit;
        $event  = self::$presentations[0];

        $params = [
            'id'       => $summit->getId(),
            'event_id' => $event->getId(),
            'filter'   => ['status=='.RSVPInvitation::Status_Pending],
        ];

        $headers = array_merge($this->getAuthHeaders(), ['CONTENT_TYPE' => 'application/json']);

        $payload = [
            'email_flow_event' => RSVPInviteEmail::EVENT_SLUG,
            'invitations_ids' => [1,2,3],
            'excluded_invitations_ids' => [4,5],
            'test_email_recipient' => 'qa@example.org',
            'outcome_email_recipient' => 'ops@example.org',
        ];

        $this->service_mock
            ->shouldReceive('triggerSend')
            ->once()
            ->withArgs(function ($evt, $body, $filterRaw) use ($event, $payload, $params) {
                $this->assertEquals($event->getId(), $evt->getId());
                $this->assertEquals($payload['email_flow_event'], $body['email_flow_event']);
                $this->assertEquals($params['filter'], $filterRaw);
                return true;
            });

        $response = $this->action(
            'PUT',
            'OAuth2RSVPInvitationApiController@send',
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($payload)
        );

        $this->assertResponseStatus(200);
    }

    // -------------------- addInvitation (POST JSON) --------------------

    public function testAddInvitation201()
    {
        $summit = self::$summit;
        $event  = self::$presentations[0];
        $summit_attendee = Mockery::mock(SummitAttendee::class)->makePartial();
        $invitation = new RSVPInvitation($event, $summit_attendee);

        $this->service_mock
            ->shouldReceive('add')
            ->once()
            ->withArgs(function ($evt, $payload) use ($event) {
                $this->assertEquals($event->getId(), $evt->getId());
                $this->assertEquals(123, $payload['invitee_id']);
                return true;
            })
            ->andReturn($invitation);

        $params = [
            'id'       => $summit->getId(),
            'event_id' => $event->getId(),
        ];

        $headers = array_merge($this->getAuthHeaders(), ['CONTENT_TYPE' => 'application/json']);

        $payload = ['invitee_id' => 123];

        $response = $this->action(
            'POST',
            'OAuth2RSVPInvitationApiController@addInvitation',
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($payload)
        );

        $this->assertResponseStatus(201);
        $this->assertNotEmpty($response->getContent());
    }

    public function testAddInvitation412WhenMissingInviteeId()
    {
        $params = [
            'id'       => self::$summit->getId(),
            'event_id' => self::$presentations[0]->getId(),
        ];

        $headers = array_merge($this->getAuthHeaders(), ['CONTENT_TYPE' => 'application/json']);

        $response = $this->action(
            'POST',
            'OAuth2RSVPInvitationApiController@addInvitation',
            $params,
            [],
            [],
            [],
            $headers,
            json_encode([]) // missing invitee_id
        );

        $this->assertResponseStatus(412);
        $this->service_mock->shouldNotReceive('add');
    }

    public function testAddInvitation403WhenNoCurrentUser()
    {
        $this->resource_server_context_mock->shouldReceive('getCurrentUser')->andReturn(null);

        $params = [
            'id'       => self::$summit->getId(),
            'event_id' => self::$presentations[0]->getId(),
        ];

        $headers = array_merge($this->getAuthHeaders(), ['CONTENT_TYPE' => 'application/json']);

        $response = $this->action(
            'POST',
            'OAuth2RSVPInvitationApiController@addInvitation',
            $params,
            [],
            [],
            [],
            $headers,
            json_encode(['invitee_id' => 1])
        );

        $this->assertResponseStatus(403);
        $this->service_mock->shouldNotReceive('add');
    }

    public function testAddInvitation404Summit()
    {
        $params = [
            'id'       => PHP_INT_MAX,
            'event_id' => 1,
        ];

        $headers = array_merge($this->getAuthHeaders(), ['CONTENT_TYPE' => 'application/json']);

        $response = $this->action(
            'POST',
            'OAuth2RSVPInvitationApiController@addInvitation',
            $params,
            [],
            [],
            [],
            $headers,
            json_encode(['invitee_id' => 1])
        );

        $this->assertResponseStatus(404);
        $this->service_mock->shouldNotReceive('add');
    }

    public function testAddInvitation404Event()
    {
        $params = [
            'id'       => self::$summit->getId(),
            'event_id' => 999999,
        ];

        $headers = array_merge($this->getAuthHeaders(), ['CONTENT_TYPE' => 'application/json']);

        $response = $this->action(
            'POST',
            'OAuth2RSVPInvitationApiController@addInvitation',
            $params,
            [],
            [],
            [],
            $headers,
            json_encode(['invitee_id' => 1])
        );

        $this->assertResponseStatus(404);
        $this->service_mock->shouldNotReceive('add');
    }

    // -------------------- delete (one) --------------------

    public function testDeleteInvitation204()
    {
        $summit = self::$summit;
        $event  = self::$presentations[0];

        $this->service_mock
            ->shouldReceive('delete')
            ->once()
            ->withArgs(function ($evt, $invId) use ($event) {
                $this->assertEquals($event->getId(), $evt->getId());
                $this->assertEquals(123, $invId);
                return true;
            });

        $params = [
            'id'            => $summit->getId(),
            'event_id'      => $event->getId(),
            'invitation_id' => 123,
        ];

        $response = $this->action(
            'DELETE',
            'OAuth2RSVPInvitationApiController@delete',
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders()
        );

        $this->assertResponseStatus(204);
        $this->assertEquals('', $response->getContent());
    }

    public function testDeleteInvitation403WhenNoCurrentUser()
    {
        $this->resource_server_context_mock->shouldReceive('getCurrentUser')->andReturn(null);

        $params = [
            'id'            => self::$summit->getId(),
            'event_id'      => self::$presentations[0]->getId(),
            'invitation_id' => 1,
        ];

        $response = $this->action(
            'DELETE',
            'OAuth2RSVPInvitationApiController@delete',
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders()
        );

        $this->assertResponseStatus(403);
        $this->service_mock->shouldNotReceive('delete');
    }

    public function testDeleteInvitation404Summit()
    {
        $params = [
            'id'            => PHP_INT_MAX,
            'event_id'      => 1,
            'invitation_id' => 1,
        ];

        $response = $this->action(
            'DELETE',
            'OAuth2RSVPInvitationApiController@delete',
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders()
        );

        $this->assertResponseStatus(404);
        $this->service_mock->shouldNotReceive('delete');
    }

    public function testDeleteInvitation404Event()
    {
        $params = [
            'id'            => self::$summit->getId(),
            'event_id'      => 999999,
            'invitation_id' => 1,
        ];

        $response = $this->action(
            'DELETE',
            'OAuth2RSVPInvitationApiController@delete',
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders()
        );

        $this->assertResponseStatus(404);
        $this->service_mock->shouldNotReceive('delete');
    }

    // -------------------- deleteAll --------------------

    public function testDeleteAll204()
    {
        $summit = self::$summit;
        $event  = self::$presentations[0];

        $this->service_mock
            ->shouldReceive('deleteAll')
            ->once()
            ->withArgs(function ($evt, $member) use ($event) {
                $this->assertEquals($event->getId(), $evt->getId());
                $this->assertInstanceOf(Member::class, $member);
                return true;
            });

        $params = [
            'id'       => $summit->getId(),
            'event_id' => $event->getId(),
        ];

        $response = $this->action(
            'DELETE',
            'OAuth2RSVPInvitationApiController@deleteAll',
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders()
        );

        $this->assertResponseStatus(204);
    }

    public function testDeleteAll403WhenNoCurrentUser()
    {
        $this->resource_server_context_mock->shouldReceive('getCurrentUser')->andReturn(null);

        $params = [
            'id'       => self::$summit->getId(),
            'event_id' => self::$presentations[0]->getId(),
        ];

        $response = $this->action(
            'DELETE',
            'OAuth2RSVPInvitationApiController@deleteAll',
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders()
        );

        $this->assertResponseStatus(403);
        $this->service_mock->shouldNotReceive('deleteAll');
    }

    public function testDeleteAll404Summit()
    {
        $params = [
            'id'       => PHP_INT_MAX,
            'event_id' => 1,
        ];

        $response = $this->action(
            'DELETE',
            'OAuth2RSVPInvitationApiController@deleteAll',
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders()
        );

        $this->assertResponseStatus(404);
        $this->service_mock->shouldNotReceive('deleteAll');
    }

    public function testDeleteAll404Event()
    {
        $params = [
            'id'       => self::$summit->getId(),
            'event_id' => 999999,
        ];

        $response = $this->action(
            'DELETE',
            'OAuth2RSVPInvitationApiController@deleteAll',
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders()
        );

        $this->assertResponseStatus(404);
        $this->service_mock->shouldNotReceive('deleteAll');
    }

    // -------------------- public: getInvitationByToken --------------------

    public function testGetInvitationByToken200()
    {
        $summit = self::$summit;
        $event  = self::$presentations[0];

        $summit_attendee = Mockery::mock(SummitAttendee::class)->makePartial();
        $invitation = new RSVPInvitation($event, $summit_attendee);

        $this->service_mock
            ->shouldReceive('getInvitationBySummitEventAndToken')
            ->once()
            ->withArgs(function ($evt, $token) use ($event) {
                $this->assertEquals($event->getId(), $evt->getId());
                $this->assertEquals('abc123', $token);
                return true;
            })
            ->andReturn($invitation);

        $params = [
            'id'       => $summit->getId(),
            'event_id' => $event->getId(),
            'token'    => 'abc123',
        ];

        $response = $this->action(
            'GET',
            'OAuth2RSVPInvitationApiController@getInvitationByToken',
            $params
        );

        $this->assertResponseStatus(200);
        $this->assertNotEmpty($response->getContent());
    }

    public function testGetInvitationByToken404Summit()
    {
        $params = [
            'id'       => PHP_INT_MAX,
            'event_id' => 1,
            'token'    => 'abc123',
        ];

        $response = $this->action(
            'GET',
            'OAuth2RSVPInvitationApiController@getInvitationByToken',
            $params
        );

        $this->assertResponseStatus(404);
    }

    public function testGetInvitationByToken404Event()
    {
        $params = [
            'id'       => self::$summit->getId(),
            'event_id' => 999999,
            'token'    => 'abc123',
        ];

        $response = $this->action(
            'GET',
            'OAuth2RSVPInvitationApiController@getInvitationByToken',
            $params
        );

        $this->assertResponseStatus(404);
    }

    // -------------------- public: acceptByToken --------------------

    public function testAcceptByToken200()
    {
        $summit = self::$summit;
        $event  = self::$presentations[0];

        $summit_attendee = Mockery::mock(SummitAttendee::class)->makePartial();
        $invitation = new RSVPInvitation($event, $summit_attendee);
        $this->service_mock
            ->shouldReceive('acceptInvitationBySummitEventAndToken')
            ->once()
            ->withArgs(function ($evt, $token) use ($event) {
                $this->assertEquals($event->getId(), $evt->getId());
                $this->assertEquals('abc123', $token);
                return true;
            })
            ->andReturn($invitation);

        $params = [
            'id'       => $summit->getId(),
            'event_id' => $event->getId(),
            'token'    => 'abc123',
        ];

        $response = $this->action(
            'PUT',
            'OAuth2RSVPInvitationApiController@acceptByToken',
            $params
        );

        $this->assertResponseStatus(200);
        $this->assertNotEmpty($response->getContent());
    }

    public function testAcceptByToken404Summit()
    {
        $params = [
            'id'       => PHP_INT_MAX,
            'event_id' => 1,
            'token'    => 'abc123',
        ];

        $response = $this->action(
            'PUT',
            'OAuth2RSVPInvitationApiController@acceptByToken',
            $params
        );

        $this->assertResponseStatus(404);
    }

    public function testAcceptByToken404Event()
    {
        $params = [
            'id'       => self::$summit->getId(),
            'event_id' => 999999,
            'token'    => 'abc123',
        ];

        $response = $this->action(
            'PUT',
            'OAuth2RSVPInvitationApiController@acceptByToken',
            $params
        );

        $this->assertResponseStatus(404);
    }

    // -------------------- public: rejectByToken --------------------


    public function testRejectByToken200()
    {
        $summit = self::$summit;
        $event  = self::$presentations[0];

        $summit_attendee = Mockery::mock(SummitAttendee::class)->makePartial();
        $invitation = new RSVPInvitation($event, $summit_attendee);

        $this->service_mock
            ->shouldReceive('rejectInvitationBySummitEventAndToken')
            ->once()
            ->withArgs(function ($evt, $token) use ($event) {
                $this->assertEquals($event->getId(), $evt->getId());
                $this->assertEquals('abc123', $token);
                return true;
            })
            ->andReturn($invitation);

        $params = [
            'id'       => $summit->getId(),
            'event_id' => $event->getId(),
            'token'    => 'abc123',
        ];

        $response = $this->action(
            'DELETE',
            'OAuth2RSVPInvitationApiController@rejectByToken',
            $params
        );

        $this->assertResponseStatus(200);
        $this->assertNotEmpty($response->getContent());
    }

    public function testRejectByToken404Summit()
    {
        $params = [
            'id'       => PHP_INT_MAX,
            'event_id' => 1,
            'token'    => 'abc123',
        ];

        $response = $this->action(
            'DELETE',
            'OAuth2RSVPInvitationApiController@rejectByToken',
            $params
        );

        $this->assertResponseStatus(404);
    }

    public function testRejectByToken404Event()
    {
        $params = [
            'id'       => self::$summit->getId(),
            'event_id' => 999999,
            'token'    => 'abc123',
        ];

        $response = $this->action(
            'DELETE',
            'OAuth2RSVPInvitationApiController@rejectByToken',
            $params
        );

        $this->assertResponseStatus(404);
    }
}
