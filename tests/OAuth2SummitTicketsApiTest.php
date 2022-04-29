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

use Illuminate\Http\UploadedFile;
use models\summit\IPaymentConstants;
use models\summit\PaymentGatewayProfileFactory;

/**
 * Class OAuth2SummitTicketsApiTest
 */
final class OAuth2SummitTicketsApiTest extends ProtectedApiTest
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

    protected function setUp(): void
    {
        parent::setUp();
        self::$test_secret_key = env('TEST_STRIPE_SECRET_KEY');
        self::$test_public_key = env('TEST_STRIPE_PUBLISHABLE_KEY');
        self::$live_secret_key = env('LIVE_STRIPE_SECRET_KEY');
        self::$live_public_key = env('LIVE_STRIPE_PUBLISHABLE_KEY');

        self::insertTestData();
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
        self::$em->persist(self::$summit);
        self::$em->flush();
    }

    protected function tearDown(): void
    {
        self::clearTestData();
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
            'expand' => 'order,refund_requests,refund_requests.requested_by,refund_requests.ticket,refund_requests.ticket.ticket_type,refund_requests.ticket.refund_requests'
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
        $this->assertTrue($ticket->refund_requests[0]->refunded_amount == 1.5);
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
            'amount' => $ticket->getFinalAmount(),
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
        $this->assertTrue($ticket1->refunded_amount == $ticket->getFinalAmount());

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
            'amount' => $ticket->getFinalAmount() + 10.0,
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
            //'id' => self::$summit->getId(),
            'id' => 13,
            'page' => 1,
            'per_page' => 10,
            'filter' => [
                'owner_first_name@@A,owner_first_name@@T',
                'has_badge==1',
                'has_owner==1',
            ],
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
attendee_email,attendee_first_name,attendee_last_name,ticket_type_name,badge_type_name,Bloomreach Connect Summit - Day 1,User Group - Day 2,Tech Track - Day 3,Partner Summit - Day 4
smarcet+json12@gmail.com,Jason12,Marcet,General Admission,General Admission,1,1,1,1
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

        $data = [
            'badge_type_id' => self::$badge_type_2->getId(),
            'features' => [self::$badge_features[0]->getId(), self::$badge_features[1]->getId()]
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
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

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE" => "application/json"
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


    function testPrintAttendeeBadge()
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
            "OAuth2SummitTicketApiController@printAttendeeBadge",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(412);
    }
}