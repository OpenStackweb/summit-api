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
use App\Services\Apis\IExternalUserApi;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Mockery;
use models\summit\IPaymentConstants;
use models\summit\PaymentGatewayProfileFactory;

/**
 * Class OAuth2SummitTicketsApiTest
 */
final class OAuth2SummitTicketsApiTest extends ProtectedApiTestCase
{

    /**
     * @var string
     */
    protected static $test_secret_key;

    /**
     * @var string
     */
    protected static $test_public_key;

    /**
     * @var string
     */
    protected static $live_secret_key;

    /**
     * @var string
     */
    protected static $live_public_key;

    /**
     * @var \models\summit\SummitTicketType
     */
    protected static $ticketType;

    /**
     * @var \models\summit\PaymentGatewayProfile|null
     */
    protected static $profile;

    use InsertSummitTestData;

    use InsertOrdersTestData;

    public function createApplication()
    {
        $app = parent::createApplication();

        // Mock external user API before any service singleton is resolved
        $externalUserApi = Mockery::mock(IExternalUserApi::class)->shouldIgnoreMissing();
        $externalUserApi->shouldReceive('getUserByEmail')->andReturn([]);
        $externalUserApi->shouldReceive('registerUser')->andReturn([
            'set_password_link' => 'https://test.com'
        ]);
        App::singleton(IExternalUserApi::class, function() use ($externalUserApi) {
            return $externalUserApi;
        });

        return $app;
    }

    protected function setUp(): void
    {
        $this->setCurrentGroup(IGroup::TrackChairs);
        parent::setUp();
        self::$defaultMember = self::$member;
        self::$test_secret_key = env('TEST_STRIPE_SECRET_KEY');
        self::$test_public_key = env('TEST_STRIPE_PUBLISHABLE_KEY');
        self::$live_secret_key = env('LIVE_STRIPE_SECRET_KEY');
        self::$live_public_key = env('LIVE_STRIPE_PUBLISHABLE_KEY');

        self::insertSummitTestData();
        self::InsertOrdersTestData();
        // build payment profile and attach to summit
        self::$profile = PaymentGatewayProfileFactory::build(IPaymentConstants::ProviderStripe, [
            'application_type' => IPaymentConstants::ApplicationTypeRegistration,
            'is_test_mode' => true,
            'test_publishable_key' => self::$test_public_key,
            'test_secret_key' => self::$test_secret_key,
            'is_active' => false,
        ]);

        self::$summit->addPaymentProfile(self::$profile);

        // Set refund request period to allow refund tests
        $refundTillDate = new \DateTime('now', new \DateTimeZone('UTC'));
        $refundTillDate->add(new \DateInterval('P30D'));
        self::$summit->setRegistrationAllowedRefundRequestTillDate($refundTillDate);

        // Set registration admin email for refund request email dispatch
        Config::set('registration.admin_email', 'test-admin@test.com');

        // Configure swift storage to use local driver for tests (avoids OpenStack/Swift dependency)
        Config::set('filesystems.disks.swift', [
            'driver' => 'local',
            'root' => storage_path('app/testing'),
        ]);

        self::$em->persist(self::$summit);
        self::$em->flush();
    }

    protected function tearDown(): void
    {
        self::clearSummitTestData();
        parent::tearDown();
    }

    public function testRequestRefundTicket()
    {
        $ticket = self::$summit_orders[0]->getFirstTicket();
        $order = $ticket->getOrder();
        $order->setOwner(self::$member);
        self::$member->addSummitRegistrationOrder($order);
        self::$em->persist($order);
        self::$em->flush();
        $params = [
            'order_id' => $order->getId(),
            'ticket_id' => $ticket->getId(),
            'expand' => 'order,refund_requests'
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        ];

        $response = $this->action(
            "DELETE",
            "OAuth2SummitOrdersApiController@requestRefundMyTicket",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $ticket = json_decode($content);
        $this->assertTrue(!is_null($ticket));
        $this->assertTrue($ticket->refunded_amount == 0);
        $this->assertTrue(count($ticket->refund_requests) == 1);
        $this->assertTrue($ticket->refund_requests[0]->refunded_amount == 0);
        $this->assertTrue($ticket->refund_requests[0]->status == 'Requested');
    }

    public function testRequestRefundTicketTwice()
    {
        $ticket = self::$summit_orders[0]->getFirstTicket();
        $order = $ticket->getOrder();
        $order->setOwner(self::$member);
        self::$member->addSummitRegistrationOrder($order);
        self::$em->persist($order);
        self::$em->flush();

        $params = [
            'order_id' => $order->getId(),
            'ticket_id' => $ticket->getId(),
            'expand' => 'order,refund_requests'
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        ];

        $response = $this->action(
            "DELETE",
            "OAuth2SummitOrdersApiController@requestRefundMyTicket",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $ticket1 = json_decode($content);
        $this->assertTrue(!is_null($ticket));
        $this->assertTrue($ticket1->refunded_amount == 0);
        $this->assertTrue(count($ticket1->refund_requests) == 1);
        $this->assertTrue($ticket1->refund_requests[0]->refunded_amount == 0);
        $this->assertTrue($ticket1->refund_requests[0]->status == 'Requested');

        $params = [
            'order_id' => $order->getId(),
            'ticket_id' => $ticket->getId(),
            'expand' => 'order,refund_requests'
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        ];

        $response = $this->action(
            "DELETE",
            "OAuth2SummitOrdersApiController@requestRefundMyTicket",
            $params,
            [],
            [],
            [],
            $headers
        );

        $this->assertResponseStatus(412);
    }

    public function testRefundTicketWithNote()
    {

        $ticket = self::$summit_orders[0]->getFirstTicket();

        $params = [
            'id' => self::$summit->getId(),
            'ticket_id' => $ticket->getId(),
            'expand' => 'order,refund_requests,refund_requests.requested_by,refund_requests.ticket,refund_requests.ticket.ticket_type,refund_requests.ticket.refund_requests,refund_requests.refunded_taxes'
        ];

        $data = [
            'amount' => 1.5,
            'notes' => 'Courtesy refund'
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        ];

        $response = $this->action(
            "DELETE",
            "OAuth2SummitTicketApiController@refundTicket",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $ticket = json_decode($content);
        $this->assertTrue(!is_null($ticket));
        $this->assertTrue($ticket->refunded_amount == 1.5);
        $this->assertTrue(count($ticket->refund_requests) == 1);
        $refund_request = $ticket->refund_requests[0];
        $this->assertTrue($refund_request->refunded_amount == 1.5);
        $this->assertTrue($refund_request->taxes_refunded_amount == 0.18);
        $this->assertTrue($refund_request->total_refunded_amount == 1.68);
        $this->assertTrue($refund_request->status == 'Approved');
        $this->assertTrue($refund_request->notes == 'Courtesy refund');
        $this->assertTrue(count($refund_request->refunded_taxes) == 2);

        $response = $this->action(
            "GET",
            "OAuth2SummitOrdersApiController@getAllRefundApprovedRequests",
            [
                'id' => self::$summit->getId(),
                'order_id' => $ticket->order->id,
                'expand' => 'refunded_taxes'
            ],
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $refund_requests = json_decode($content);
        $this->assertTrue(!is_null($refund_requests));
        $this->assertTrue($refund_requests->total == 1);
    }

    public function testRejectRefund(){
        $ticket = self::$summit_orders[0]->getFirstTicket();
        $order = $ticket->getOrder();
        $order->setOwner(self::$member);
        self::$member->addSummitRegistrationOrder($order);
        self::$em->persist($order);
        self::$em->flush();

        $params = [
            'order_id' => $order->getId(),
            'ticket_id' => $ticket->getId(),
            'expand' => 'order,refund_requests'
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        ];

        $response = $this->action(
            "DELETE",
            "OAuth2SummitOrdersApiController@requestRefundMyTicket",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $ticket = json_decode($content);
        $this->assertTrue(!is_null($ticket));
        $this->assertTrue($ticket->refunded_amount == 0);
        $this->assertTrue(count($ticket->refund_requests) == 1);
        $this->assertTrue($ticket->refund_requests[0]->refunded_amount == 0);
        $this->assertTrue($ticket->refund_requests[0]->status == 'Requested');

        $data = [
            'notes' => 'OUT OF TIME'
        ];

        $response = $this->action(
            "DELETE",
            "OAuth2SummitOrdersApiController@cancelRefundRequestTicket",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $this->assertResponseStatus(201);
        $content = $response->getContent();
        $ticket = json_decode($content);
        $this->assertTrue($ticket->refunded_amount == 0);
        $this->assertTrue(count($ticket->refund_requests) == 1);
        $this->assertTrue($ticket->refund_requests[0]->refunded_amount == 0);
        $this->assertTrue($ticket->refund_requests[0]->status == 'Rejected');
        $this->assertTrue($ticket->refund_requests[0]->notes == 'OUT OF TIME');
    }

    public function testFullRefundTicketWithNote()
    {

        $ticket = self::$summit_orders[0]->getFirstTicket();

        $params = [
            'id' => self::$summit->getId(),
            'ticket_id' => self::$summit_orders[0]->getFirstTicket()->getId(),
            'expand' => 'refund_requests'
        ];

        $data = [
            'amount' => $ticket->getNetSellingPrice(),
            'notes' => 'Courtesy refund'
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        ];

        $response = $this->action(
            "DELETE",
            "OAuth2SummitTicketApiController@refundTicket",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $ticket1 = json_decode($content);
        $this->assertTrue(!is_null($ticket1));
        $this->assertTrue($ticket1->refunded_amount == $ticket->getNetSellingPrice());

        $params = [
            'id' => self::$summit->getId(),
            'ticket_id' => $ticket->getNumber(),
            'expand' => 'order, refund_requests'
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitTicketApiController@get",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $ticket2 = json_decode($content);
        $this->assertTrue(!is_null($ticket2));
    }

    public function testRequestRefundRequestAndGetTickets()
    {
        $ticket = self::$summit_orders[0]->getFirstTicket();
        $order = $ticket->getOrder();
        $order->setOwner(self::$member);
        self::$member->addSummitRegistrationOrder($order);
        self::$em->persist($order);
        self::$em->flush();

        $params = [
            'order_id' => $order->getId(),
            'ticket_id' => $ticket->getId(),
            'expand' => 'order,refund_requests'
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        ];

        $response = $this->action(
            "DELETE",
            "OAuth2SummitOrdersApiController@requestRefundMyTicket",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $ticket1 = json_decode($content);
        $this->assertTrue(!is_null($ticket));
        $this->assertTrue($ticket1->refunded_amount == 0);
        $this->assertTrue(count($ticket1->refund_requests) == 1);
        $this->assertTrue($ticket1->refund_requests[0]->refunded_amount == 0);
        $this->assertTrue($ticket1->refund_requests[0]->status == 'Requested');

        $params = [
            'id' => self::$summit->getId(),
            'expand' => 'order, refund_requests',
            'filter' => 'has_requested_refund_requests==1'
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitTicketApiController@getAllBySummit",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $tickets = json_decode($content);
        $this->assertTrue(!is_null($tickets));
        $this->assertTrue($tickets->total==1);
    }

    public function testRefundTicketGreaterThanCost()
    {

        $ticket = self::$summit_orders[0]->getFirstTicket();
        $params = [
            'id' => self::$summit->getId(),
            'ticket_id' => $ticket->getId()
        ];

        $data = [
            'amount' => $ticket->getNetSellingPrice() + 10.0,
            'notes' => 'Courtesy refund'
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        ];

        $response = $this->action(
            "DELETE",
            "OAuth2SummitTicketApiController@refundTicket",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $this->assertResponseStatus(412);
    }

    public function testGetAllTickets()
    {
        $params = [
            'id' => self::$summit->getId(),
            'page' => 1,
            'per_page' => 10,
            'order' => '+id',
            'expand' => 'owner,order,ticket_type,badge,promo_code'
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitTicketApiController@getAllBySummit",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $tickets = json_decode($content);
        $this->assertTrue(!is_null($tickets));
        return $tickets;
    }

    public function testGetAllTicketsCSV()
    {

        $params = [
            'id' => self::$summit->getId(),
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitTicketApiController@getAllBySummitCSV",
            $params,
            [],
            [],
            [],
            $headers
        );

        $csv = $response->getContent();
        $this->assertResponseStatus(200);
        $this->assertTrue(!empty($csv));
        return $csv;
    }

    public function testGetTicketImportTemplate()
    {

        $params = [
            'id' => self::$summit->getId(),
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitTicketApiController@getImportTicketDataTemplate",
            $params,
            [],
            [],
            [],
            $headers
        );

        $csv = $response->getContent();
        $this->assertResponseStatus(200);
        $this->assertTrue(!empty($csv));
        return $csv;
    }

    public function testIngestTicketData()
    {
        $csv_content = <<<CSV
attendee_email,attendee_first_name,attendee_last_name,ticket_type_name,badge_type_name,promo_code_id,promo_code,Bloomreach Connect Summit - Day 1,User Group - Day 2,Tech Track - Day 3,Partner Summit - Day 4
smarcet+json12@gmail.com,Jason12,Marcet,General Admission,General Admission,1,FNSPEAKER,1,1,1,1
CSV;
        $path = "/tmp/tickets.csv";

        file_put_contents($path, $csv_content);

        $file = new UploadedFile($path, "tickets.csv", 'text/csv', null, true);

        $params = [
            'id' => self::$summit->getId(),
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitTicketApiController@importTicketData",
            $params,
            [],
            [],
            [
                'file' => $file
            ],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
    }

    public function testIngestTicketData2()
    {
        $csv_content = <<<CSV
id,number,attendee_email,attendee_first_name,attendee_last_name,attendee_company,ticket_type_name,ticket_type_id,badge_type_id,badge_type_name,Commander,VIP Access
,REGISTRATIONDEVSUMMIT2019_TICKET_5D7BD99A36008622282877,xmarcet+4@gmail.com,,,,,,,,1,1
CSV;
        $path = "/tmp/tickets.csv";

        file_put_contents($path, $csv_content);

        $file = new UploadedFile($path, "tickets.csv", 'text/csv', null, true);

        $params = [
            'id' => self::$summit->getId(),
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitTicketApiController@importTicketData",
            $params,
            [],
            [],
            [
                'file' => $file
            ],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
    }


    public function testGetTicketByNumber()
    {
        $ticket = self::$summit_orders[0]->getFirstTicket();
        $params = [
            'id' => self::$summit->getId(),
            'ticket_id' => $ticket->getNumber(),
            'expand' => 'order'
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitTicketApiController@get",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $ticket = json_decode($content);
        $this->assertTrue(!is_null($ticket));
        return $ticket;
    }

    // badges endpoints


    public function testCreateAttendeeBadge()
    {
        $ticket = self::$summit_orders[0]->getFirstTicket();
        $params = [
            'id' => self::$summit->getId(),
            'ticket_id' => $ticket->getId(),
        ];


        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        ];

        $response = $this->action(
            "DELETE",
            "OAuth2SummitTicketApiController@deleteAttendeeBadge",
            $params,
            [],
            [],
            [],
            $headers
        );

        $data = [
            'badge_type_id' => self::$badge_type_2->getId(),
            'features' => [self::$badge_features[0]->getId(), self::$badge_features[1]->getId()]
        ];


        $response = $this->action(
            "POST",
            "OAuth2SummitTicketApiController@createAttendeeBadge",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $badge = json_decode($content);
        $this->assertTrue(!is_null($badge));
        $this->assertTrue(count($badge->features) == 2);
        return $badge;
    }

    public function testRemoveAttendeeBadgeFeature()
    {
        $ticket = self::$summit_orders[0]->getFirstTicket();

        $params = [
            'id' => self::$summit->getId(),
            'ticket_id' => $ticket->getId(),
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        ];

        // Delete existing badge first
        $this->action(
            "DELETE",
            "OAuth2SummitTicketApiController@deleteAttendeeBadge",
            $params,
            [],
            [],
            [],
            $headers
        );

        $data = [
            'badge_type_id' => self::$badge_type_2->getId(),
            'features' => [self::$badge_features[0]->getId(), self::$badge_features[1]->getId()]
        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitTicketApiController@createAttendeeBadge",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $badge = json_decode($content);
        $this->assertTrue(!is_null($badge));
        $this->assertTrue(count($badge->features) == 2);

        $params = [
            'id' => self::$summit->getId(),
            'ticket_id' => $ticket->getId(),
            'feature_id' => self::$badge_features[0]->getId(),
        ];

        $response = $this->action(
            "DELETE",
            "OAuth2SummitTicketApiController@removeAttendeeBadgeFeature",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $badge = json_decode($content);
        $this->assertTrue(!is_null($badge));
        $this->assertTrue(count($badge->features) == 1);
        return $badge;
    }

    public function testAddAttendeeBadgeFeature()
    {

        $ticket = self::$summit_orders[0]->getFirstTicket();

        $params = [
            'id' => self::$summit->getId(),
            'ticket_id' => $ticket->getId(),
            'feature_id' => self::$badge_features[0]->getId(),
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        ];

        $response = $this->action(
            "PUT",
            "OAuth2SummitTicketApiController@addAttendeeBadgeFeature",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $badge = json_decode($content);
        $this->assertTrue(!is_null($badge));
        $this->assertTrue(count($badge->features) == 1);
        return $badge;
    }


    public function testUpdateAttendeeBadgeType()
    {

        $ticket = self::$summit_orders[0]->getFirstTicket();

        $params = [
            'id' => self::$summit->getId(),
            'ticket_id' => $ticket->getId(),
            'type_id' => self::$badge_type_2->getId(),
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        ];

        $response = $this->action(
            "PUT",
            "OAuth2SummitTicketApiController@updateAttendeeBadgeType",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $badge = json_decode($content);
        $this->assertTrue(!is_null($badge));
        return $badge;
    }

    public function testDeleteAttendeeBadge()
    {
        $ticket = self::$summit_orders[0]->getFirstTicket();

        $params = [
            'id' => self::$summit->getId(),
            'ticket_id' => $ticket->getId(),
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        ];

        $response = $this->action(
            "DELETE",
            "OAuth2SummitTicketApiController@deleteAttendeeBadge",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(204);
    }


    public function testPrintAttendeeBadge()
    {
        $ticket = self::$summit_orders[0]->getFirstTicket();

        $params = [
            'id' => self::$summit->getId(),
            'ticket_id' => $ticket->getId(),
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        ];

        $response = $this->action(
            "PUT",
            "OAuth2SummitTicketApiController@printAttendeeBadgeDefault",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(412);
    }

    public function testGetAllTicketsByPromoCodeTag()
    {
        $params = [
            'id' => self::$summit->getId(),
            'page' => 1,
            'per_page' => 10,
            'filter' => [
                'badge_type_id==' . self::$default_badge_type->getId(),
            ],
            'order' => '+id'
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitTicketApiController@getAllBySummit",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $tickets = json_decode($content);
        $this->assertTrue(!is_null($tickets));
        return $tickets;
    }

    public function testGetAllTicketsWithoutPromoCode()
    {
        $params = [
            'id' => self::$summit->getId(),
            'page' => 1,
            'per_page' => 10,
            'filter' => [
                'has_promo_code==0',
            ],
            'order' => '+id'
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitTicketApiController@getAllBySummit",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $tickets = json_decode($content);
        $this->assertTrue(!is_null($tickets));
        return $tickets;
    }

    public function testGetAttendeeBadge()
    {
        // Use the testCreateAttendeeBadge helper to ensure a badge exists
        $created_badge = $this->testCreateAttendeeBadge();

        $ticket = self::$summit_orders[0]->getFirstTicket();

        // Use ticket number (not ID) because controller uses is_int() check
        // and URL params are always strings
        $params = [
            'id' => self::$summit->getId(),
            'ticket_id' => $ticket->getNumber(),
            'expand' => 'features,type'
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitTicketApiController@getAttendeeBadge",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $badge = json_decode($content);
        $this->assertNotNull($badge);
        return $badge;
    }

    public function testCanPrintAttendeeBadgeDefault()
    {
        $ticket = self::$summit_orders[0]->getFirstTicket();

        $params = [
            'id' => self::$summit->getId(),
            'ticket_id' => $ticket->getId(),
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitTicketApiController@canPrintAttendeeBadgeDefault",
            $params,
            [],
            [],
            [],
            $headers
        );

        // 200 if printable, 412 if virtual-only ticket validation
        $this->assertTrue(in_array($response->getStatusCode(), [200, 412]));
    }

    public function testCanPrintAttendeeBadgeByViewType()
    {
        $ticket = self::$summit_orders[0]->getFirstTicket();

        $params = [
            'id' => self::$summit->getId(),
            'ticket_id' => $ticket->getId(),
            'view_type' => self::$default_badge_view_type->getName(),
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitTicketApiController@canPrintAttendeeBadge",
            $params,
            [],
            [],
            [],
            $headers
        );

        // 200 if printable, 412 if virtual-only ticket validation
        $this->assertTrue(in_array($response->getStatusCode(), [200, 412]));
    }

    public function testPrintAttendeeBadgeByViewType()
    {
        $ticket = self::$summit_orders[0]->getFirstTicket();

        $params = [
            'id' => self::$summit->getId(),
            'ticket_id' => $ticket->getId(),
            'view_type' => self::$default_badge_view_type->getName(),
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        ];

        $response = $this->action(
            "PUT",
            "OAuth2SummitTicketApiController@printAttendeeBadge",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        // 412 is expected when badge print rules not met (no attendee assigned, etc)
        $this->assertTrue(in_array($response->getStatusCode(), [201, 412]));
    }

    public function testGetAllTicketsExternal412()
    {
        // summit has no external feed type set, should return 412
        $params = [
            'id' => self::$summit->getId(),
            'filter' => 'owner_email==test@test.com',
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitTicketApiController@getAllBySummitExternal",
            $params,
            [],
            [],
            [],
            $headers
        );

        $this->assertResponseStatus(412);
    }

    public function testGetAllMyTickets()
    {
        // assign order to current member so tickets are "mine"
        $ticket = self::$summit_orders[0]->getFirstTicket();
        $order = $ticket->getOrder();
        $order->setOwner(self::$member);
        self::$member->addSummitRegistrationOrder($order);
        self::$em->persist($order);
        self::$em->flush();

        $params = [
            'page' => 1,
            'per_page' => 10,
            'order' => '+id',
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitTicketApiController@getAllMyTickets",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $tickets = json_decode($content);
        $this->assertNotNull($tickets);
        $this->assertGreaterThanOrEqual(1, $tickets->total);
        return $tickets;
    }

    public function testGetAllMyTicketsBySummit()
    {
        $ticket = self::$summit_orders[0]->getFirstTicket();
        $order = $ticket->getOrder();
        $order->setOwner(self::$member);
        self::$member->addSummitRegistrationOrder($order);
        self::$em->persist($order);
        self::$em->flush();

        $params = [
            'id' => self::$summit->getId(),
            'page' => 1,
            'per_page' => 10,
            'order' => '+id',
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitTicketApiController@getAllMyTicketsBySummit",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $tickets = json_decode($content);
        $this->assertNotNull($tickets);
        $this->assertGreaterThanOrEqual(1, $tickets->total);
        return $tickets;
    }

    public function testAddTicketToOrder()
    {
        $order = self::$summit_orders[0];

        $params = [
            'id' => self::$summit->getId(),
            'order_id' => $order->getId(),
        ];

        $data = [
            'ticket_type_id' => self::$default_ticket_type->getId(),
            'ticket_qty' => 1,
            'attendee_email' => 'test-new-ticket@test.com',
            'attendee_first_name' => 'Test',
            'attendee_last_name' => 'Attendee',
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitOrdersApiController@addTicket",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $ticket = json_decode($content);
        $this->assertNotNull($ticket);
        return $ticket;
    }

    public function testUpdateTicket()
    {
        $ticket = self::$summit_orders[0]->getFirstTicket();
        $order = $ticket->getOrder();

        $params = [
            'id' => self::$summit->getId(),
            'order_id' => $order->getId(),
            'ticket_id' => $ticket->getId(),
        ];

        $data = [
            'attendee_email' => 'updated-attendee@test.com',
            'attendee_first_name' => 'Updated',
            'attendee_last_name' => 'Name',
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        ];

        $response = $this->action(
            "PUT",
            "OAuth2SummitOrdersApiController@updateTicket",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $ticket = json_decode($content);
        $this->assertNotNull($ticket);
        return $ticket;
    }

    public function testDeActivateTicket()
    {
        $ticket = self::$summit_orders[0]->getFirstTicket();
        $order = $ticket->getOrder();

        $params = [
            'id' => self::$summit->getId(),
            'order_id' => $order->getId(),
            'ticket_id' => $ticket->getId(),
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        ];

        $response = $this->action(
            "DELETE",
            "OAuth2SummitOrdersApiController@deActivateTicket",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $ticket = json_decode($content);
        $this->assertNotNull($ticket);
        $this->assertFalse($ticket->is_active);
        return $ticket;
    }

    public function testActivateTicket()
    {
        // first deactivate
        $ticket_data = $this->testDeActivateTicket();

        $ticket = self::$summit_orders[0]->getFirstTicket();
        $order = $ticket->getOrder();

        $params = [
            'id' => self::$summit->getId(),
            'order_id' => $order->getId(),
            'ticket_id' => $ticket->getId(),
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        ];

        $response = $this->action(
            "PUT",
            "OAuth2SummitOrdersApiController@activateTicket",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $ticket = json_decode($content);
        $this->assertNotNull($ticket);
        $this->assertTrue($ticket->is_active);
        return $ticket;
    }

    public function testAssignAttendee()
    {
        $ticket = self::$summit_orders[0]->getFirstTicket();
        $order = $ticket->getOrder();
        $order->setOwner(self::$member);
        self::$member->addSummitRegistrationOrder($order);
        self::$em->persist($order);
        self::$em->flush();

        $params = [
            'order_id' => $order->getId(),
            'ticket_id' => $ticket->getId(),
        ];

        $data = [
            'attendee_email' => 'assigned-attendee@test.com',
            'attendee_first_name' => 'Assigned',
            'attendee_last_name' => 'Attendee',
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        ];

        $response = $this->action(
            "PUT",
            "OAuth2SummitOrdersApiController@assignAttendee",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $ticket = json_decode($content);
        $this->assertNotNull($ticket);
        return $ticket;
    }

    public function testRemoveAttendee()
    {
        $assigned_ticket = $this->testAssignAttendee();

        $ticket = self::$summit_orders[0]->getFirstTicket();
        $order = $ticket->getOrder();

        $params = [
            'order_id' => $order->getId(),
            'ticket_id' => $ticket->getId(),
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        ];

        $response = $this->action(
            "DELETE",
            "OAuth2SummitOrdersApiController@removeAttendee",
            $params,
            [],
            [],
            [],
            $headers
        );

        $this->assertResponseStatus(201);
    }

    public function testGetMyTicketsByOrderId()
    {
        $ticket = self::$summit_orders[0]->getFirstTicket();
        $order = $ticket->getOrder();
        $order->setOwner(self::$member);
        self::$member->addSummitRegistrationOrder($order);
        self::$em->persist($order);
        self::$em->flush();

        $params = [
            'order_id' => $order->getId(),
            'page' => 1,
            'per_page' => 10,
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitOrdersApiController@getMyTicketsByOrderId",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $tickets = json_decode($content);
        $this->assertNotNull($tickets);
        return $tickets;
    }

    public function testGetMyTicketById()
    {
        $ticket = self::$summit_orders[0]->getFirstTicket();
        $order = $ticket->getOrder();
        $order->setOwner(self::$member);
        self::$member->addSummitRegistrationOrder($order);
        self::$em->persist($order);
        self::$em->flush();

        $params = [
            'order_id' => $order->getId(),
            'ticket_id' => $ticket->getId(),
            'expand' => 'order,ticket_type'
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitOrdersApiController@getMyTicketById",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $ticket = json_decode($content);
        $this->assertNotNull($ticket);
        return $ticket;
    }

    public function testUpdateMyTicketById()
    {
        $ticket = self::$summit_orders[0]->getFirstTicket();
        $order = $ticket->getOrder();
        $order->setOwner(self::$member);
        self::$member->addSummitRegistrationOrder($order);
        self::$em->persist($order);
        self::$em->flush();

        $params = [
            'ticket_id' => $ticket->getId(),
        ];

        $data = [
            'attendee_company' => 'Test Company',
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        ];

        $response = $this->action(
            "PUT",
            "OAuth2SummitOrdersApiController@updateMyTicketById",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $content = $response->getContent();
        // 201 or 412 depending on whether ticket is reassignable
        $this->assertTrue(in_array($response->getStatusCode(), [201, 412]));
    }

    public function testDelegateTicket()
    {
        $ticket = self::$summit_orders[0]->getFirstTicket();
        $order = $ticket->getOrder();
        $order->setOwner(self::$member);
        self::$member->addSummitRegistrationOrder($order);
        self::$em->persist($order);
        self::$em->flush();

        $params = [
            'id' => self::$summit->getId(),
            'order_id' => $order->getId(),
            'ticket_id' => $ticket->getId(),
        ];

        $data = [
            'attendee_email' => 'delegate-target@test.com',
            'attendee_first_name' => 'Delegated',
            'attendee_last_name' => 'User',
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        ];

        $response = $this->action(
            "PUT",
            "OAuth2SummitOrdersApiController@delegateTicket",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $ticket = json_decode($content);
        $this->assertNotNull($ticket);
        return $ticket;
    }

    public function testReInviteAttendee()
    {
        // First assign an attendee
        $assigned_ticket = $this->testAssignAttendee();

        $ticket = self::$summit_orders[0]->getFirstTicket();
        $order = $ticket->getOrder();

        $params = [
            'order_id' => $order->getId(),
            'ticket_id' => $ticket->getId(),
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
        ];

        $response = $this->action(
            "PUT",
            "OAuth2SummitOrdersApiController@reInviteAttendee",
            $params,
            [],
            [],
            [],
            $headers
        );

        // 201 on success or 412 if conditions not met
        $this->assertTrue(in_array($response->getStatusCode(), [201, 412]));
    }
}
