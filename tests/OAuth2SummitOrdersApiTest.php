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
use Illuminate\Support\Facades\App;
use models\summit\PaymentGatewayProfileFactory;
use models\summit\IPaymentConstants;
use App\Models\Foundation\Summit\Factories\SummitTicketTypeFactory;
use App\Models\Foundation\Summit\Factories\SummitBadgeTypeFactory;
use services\model\ISummitService;

/**
 * Class OAuth2SummitOrdersApiTest
 */
final class OAuth2SummitOrdersApiTest extends ProtectedApiTestCase
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

    protected function setUp():void
    {
        parent::setUp();
        self::$test_secret_key = env('TEST_STRIPE_SECRET_KEY');
        self::$test_public_key = env('TEST_STRIPE_PUBLISHABLE_KEY');
        self::$live_secret_key = env('LIVE_STRIPE_SECRET_KEY');
        self::$live_public_key = env('LIVE_STRIPE_PUBLISHABLE_KEY');
        self::$defaultMember = self::$member;
        self::$defaultMember2 = self::$member2;
        self::insertSummitTestData();
        // build payment profile and attach to summit
        self::$profile = PaymentGatewayProfileFactory::build(IPaymentConstants::ProviderStripe, [
            'application_type'     => IPaymentConstants::ApplicationTypeRegistration,
            'is_test_mode'         => true,
            'test_publishable_key' => self::$test_public_key,
            'test_secret_key'      => self::$test_secret_key,
            'is_active'            => false,
        ]);

        // build default badge type

        $defaultBadge = SummitBadgeTypeFactory::build([
            'name' => 'DEFAULT',
            'is_default' => true,
        ]);

        // build ticket type

        self::$ticketType = SummitTicketTypeFactory::build(self::$summit, [
            'name'            => 'TICKET_1',
            'cost'            => 100,
            'quantity_2_sell' => 1000,
        ]);

        self::$summit->addPaymentProfile(self::$profile);
        self::$summit->addBadgeType($defaultBadge);
        self::$summit->addTicketType(self::$ticketType);

        self::$em->persist(self::$summit);
        self::$em->flush();

        // Generate hashes on orders and tickets for hash-dependent tests
        foreach (self::$summit->getOrders() as $order) {
            $order->generateHash();
            foreach ($order->getTickets() as $ticket) {
                $ticket->generateHash();
            }
        }
        self::$em->persist(self::$summit);
        self::$em->flush();
    }

    protected function tearDown():void
    {
        self::clearSummitTestData();
        parent::tearDown();
    }

    /**
     * @return mixed
     */
    public function testGetAllMyOrders(){
        $params = [

            'page'     => 1,
            'per_page' => 10,
            'order'    => '+number',
            'expand'   => 'tickets,tickets.owner,tickets.badge'
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitOrdersApiController@getAllMyOrders",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $my_orders = json_decode($content);
        $this->assertTrue(!is_null($my_orders));
        return $my_orders;
    }

    /**
     * @return mixed
     */
    public function testGetAllMyTickets(){
        $params = [

            'page'     => 1,
            'per_page' => 10,
            'order'    => '+number',
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
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
        $my_orders = json_decode($content);
        $this->assertTrue(!is_null($my_orders));
        return $my_orders;
    }

    /**
     * @return mixed
     */
    public function testGetAllTickets(){
        $params = [
            'id' => self::$summit->getId(),
            'page'     => 1,
            'per_page' => 10,
            'order'    => '+number',
            'filter' => 'final_amount>0'
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"       => "application/json"
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
        $page = json_decode($content);
        $this->assertTrue(!is_null($page));
        $this->assertNotEmpty($page->data);
        return $page;
    }

    /**
     * @return mixed
     */
    public function testGetAllFreeTickets(){
        $params = [
            'id' => self::$summit->getId(),
            'page'     => 1,
            'per_page' => 10,
            'order'    => '+number',
            'filter' => 'final_amount==0'
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"       => "application/json"
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
        $page = json_decode($content);
        $this->assertTrue(!is_null($page));
        $this->assertEmpty($page->data);
        return $page;
    }

    /**
     * @return mixed
     */
    public function testUpdateMyOrder(){
        $order = self::$summit->getOrders()->first();
        if(is_null($order)) $this->markTestSkipped('No orders in test data');

        $params = [
            'order_id' => $order->getId()
        ];

        $data = [
            'owner_company' => 'OpenStack',
            'billing_address_1' => 'Siempre Viva Av.',
            'extra_questions' => [
                ['question_id' => 1, 'answer' => 'test'],
            ]
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "PUT",
            "OAuth2SummitOrdersApiController@updateMyOrder",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $order = json_decode($content);
        $this->assertTrue(!is_null($order));
        return $order;
    }

    /**
     * @return mixed
     */
    public function testUpdateOrder(){
        $order = self::$summit->getOrders()->first();
        if(is_null($order)) $this->markTestSkipped('No orders in test data');

        $params = [
            'id' => self::$summit->getId(),
            'order_id' => $order->getId()
        ];

        $data = [
            'owner_first_name' => 'Sebastian',
            'owner_last_name' => 'Sebastian',
            'owner_email' => 'smarcet@gmail.com',
            'owner_company' => 'OpenStack',
            'billing_address_1' => 'Siempre Viva Av.',
            'extra_questions' => [
                    ['question_id' => 3, 'answer' => '1'],
                    ['question_id' => 4, 'answer' => ''],
                    ['question_id' => 5, 'answer' => ''],
            ]
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "PUT",
            "OAuth2SummitOrdersApiController@update",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $order = json_decode($content);
        $this->assertTrue(!is_null($order));
        return $order;
    }

    public function testReserveWithoutActivePaymentProfile(){
        $this->markTestSkipped('reserve endpoint is public (no auth.user middleware) but SagaFactory::build() requires non-null Member');

        self::$profile->disable();
        self::$em->persist(self::$profile);
        self::$em->flush();

        $params = [
            'id' => self::$summit->getId(),
        ];

        $data = [
            "owner_email" => "smarcet@gmail.com",
            "owner_first_name" => "Sebastian",
            "owner_last_name" => "Marcet",
            "owner_company"=>"Pumant",
            "tickets" => [
                ["type_id" => self::$ticketType->getId()],
                ["type_id" => self::$ticketType->getId()],
                ["type_id" => self::$ticketType->getId()],
            ]
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitOrdersApiController@reserve",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $content = $response->getContent();
        //$this->assertResponseStatus(412);
        $this->assertResponseStatus(201);
        $order = json_decode($content);
        $this->assertTrue(!is_null($order));
        return $order;
    }

    public function testReserveWithSummit(){
        $this->markTestSkipped('reserve endpoint is public (no auth.user middleware) but SagaFactory::build() requires non-null Member');

        $res = memory_get_peak_usage(true);

        $summitId = self::$summit->getId();
        $companyId = 5;

        $service = App::make(ISummitService::class);
        $service->addCompany($summitId, $companyId);
        $company = self::$summit->getRegistrationCompanyById($companyId);

        $params = [
            'id' => $summitId,
        ];

        $data = [
            "owner_email"       => "smarcet@gmail.com",
            "owner_first_name"  => "Sebastian",
            "owner_last_name"   => "Marcet",
            "owner_company"     => $company->getName(),
            "owner_company_id"  => $company->getId(),
            "tickets" => [
                ["type_id" => self::$ticketType->getId()],
            ]
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitOrdersApiController@reserve",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $order = json_decode($content);
        $this->assertTrue(!is_null($order));
        $res = memory_get_peak_usage(true);
        return $order;
    }

    public function testReserveWithActivePaymentProfile(){
        $this->markTestSkipped('reserve endpoint is public (no auth.user middleware) but SagaFactory::build() requires non-null Member');

        self::$profile->activate();
        self::$em->persist(self::$profile);
        self::$em->flush();

        $params = [
            'id' => self::$summit->getId(),
        ];

        $data = [
            "owner_email" => "smarcet@gmail.com",
            "owner_first_name" => "Sebastian",
            "owner_last_name" => "Marcet",
            "owner_company"=>"Pumant",
            "tickets" => [
                ["type_id" => self::$ticketType->getId()],
                ["type_id" => self::$ticketType->getId()],
                ["type_id" => self::$ticketType->getId()],
            ]
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitOrdersApiController@reserve",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $order = json_decode($content);
        $this->assertTrue(!is_null($order));
        return $order;
    }

    public function testReserveFailingPromoCode(){
        $this->markTestSkipped('reserve endpoint is public (no auth.user middleware) but SagaFactory::build() requires non-null Member');

        $params = [
            'id' => self::$summit->getId(),
        ];

        $data = [
            "owner_email" => "smarcet@gmail.com",
            "owner_first_name" => "Sebastian",
            "owner_last_name" => "Marcet",
            "owner_company"=>"Pumant",
            "tickets" => [
                ["type_id" => self::$ticketType->getId(), "promo_code"=>"test100"],
                ["type_id" => self::$ticketType->getId(), "promo_code"=>"Test100"],
                ["type_id" => self::$ticketType->getId(), "promo_code"=>"TesT100"],
            ]
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitOrdersApiController@reserve",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(412);
    }

    public function testTicketAssignmentWithoutExtraQuestions(){
            $order = self::$summit->getOrders()->first();
            if(is_null($order)) $this->markTestSkipped('No orders in test data');
            $ticket = $order->getFirstTicket();
            if(is_null($ticket)) $this->markTestSkipped('No tickets in test data');

            $params = [
                'order_id'  => $order->getId(),
                'ticket_id' => $ticket->getId(),
            ];

            $data = [
                "attendee_email" => "sebastian.jose@gmail.com",
                "attendee_first_name" => "sebastian",
                "attendee_last_name" => 'marcet',
                "attendee_company" => "pumant",
            ];

            $headers = [
                "HTTP_Authorization" => " Bearer " . $this->access_token,
                "CONTENT_TYPE"        => "application/json"
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
            $this->assertResponseStatus(412);
    }

    /**
     * @param int $summit_id
     * @param string $hash
     */
    public function testCheckoutFailedCountryValidation(){
        $order = self::$summit->getOrders()->first();
        if(is_null($order)) $this->markTestSkipped('No orders in test data');

        $params = [
            'id' => self::$summit->getId(),
            'hash' => $order->getHash()
        ];

        $data = [
            'billing_address_1'         => 'test',
            'billing_address_2'         => 'test',
            'billing_address_zip_code'  => 'test',
            'billing_address_city'      => 'test',
            'billing_address_state'     => 'test',
            'billing_address_country'   => 'test',
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "PUT",
            "OAuth2SummitOrdersApiController@checkout",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(412);
        $this->assertTrue(str_contains($content, "billing_address_country"));
    }

    /**
     * @param int $summit_id
     * @param string $hash
     */
    public function testGetTicketForEditionByOrderHash(){
        // Find a single-ticket order (endpoint requires single-ticket orders)
        $order = null;
        foreach (self::$summit->getOrders() as $o) {
            if ($o->getTickets()->count() === 1) {
                $order = $o;
                break;
            }
        }
        if(is_null($order)) $this->markTestSkipped('No single-ticket orders in test data');

        $params = [
            'id'   => self::$summit->getId(),
            'hash' => $order->getHash()
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitOrdersApiController@getMyTicketByOrderHash",
            $params,
            [],
            [],
            [],
            $headers,
           []
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $ticket = json_decode($content);
        $this->assertTrue(!is_null($ticket));
        return $ticket;
    }

    /**
     * @param int $summit_id
     * @param int $order_id
     * @param int $ticket_id
     * @return mixed
     */
    public function testUpdateTicket(){
        $order = self::$summit->getOrders()->first();
        if(is_null($order)) $this->markTestSkipped('No orders in test data');
        $ticket = $order->getFirstTicket();
        if(is_null($ticket)) $this->markTestSkipped('No tickets in test data');

        $params = [
            'id'        => self::$summit->getId(),
            'order_id'  => $order->getId(),
            'ticket_id' => $ticket->getId(),
        ];

        $data = [
            'attendee_first_name' => 'Jose Arturo',
            'attendee_last_name' => 'Campanella',
            'attendee_email' => 'jcampanella@gmail.com',
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
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
        $this->assertTrue(!is_null($ticket));
        return $ticket;
    }

    /**
     * @return mixed
     */
    public function testRevokeAttendee(){
        $order = self::$summit->getOrders()->first();
        if(is_null($order)) $this->markTestSkipped('No orders in test data');
        $ticket = $order->getFirstTicket();
        if(is_null($ticket)) $this->markTestSkipped('No tickets in test data');

        $params = [
            'order_id' => $order->getId(),
            'ticket_id' => $ticket->getId(),
        ];


        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
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

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $ticket = json_decode($content);
        $this->assertTrue(!is_null($ticket));
        return $ticket;
    }

    public function testRegenerateTicketHash(){
        $order = self::$summit->getOrders()->first();
        if(is_null($order)) $this->markTestSkipped('No orders in test data');
        $ticket = $order->getFirstTicket();
        if(is_null($ticket)) $this->markTestSkipped('No tickets in test data');

        $params = [
            'hash' => $ticket->getHash(),
        ];

        $response = $this->action(
            "PUT",
            "OAuth2SummitOrdersApiController@regenerateTicketHash",
            $params,
            [],
            [],
            []
        );

        $this->assertResponseStatus(200);
    }

    public function testGetTicketByHash(){
        $order = self::$summit->getOrders()->first();
        if(is_null($order)) $this->markTestSkipped('No orders in test data');
        $ticket = $order->getFirstTicket();
        if(is_null($ticket)) $this->markTestSkipped('No tickets in test data');

        $params = [
            'hash' => $ticket->getHash(),
            'expand' => 'owner,order,applied_taxes, applied_taxes.tax'
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitOrdersApiController@getTicketByHash",
            $params,
            [],
            [],
            []
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $ticket = json_decode($content);
        $this->assertTrue(!is_null($ticket));
        return $ticket;
    }

    public function testCreateSingleTicketOrder(){
        $this->markTestSkipped('reserve endpoint is public (no auth.user middleware) but SagaFactory::build() requires non-null Member');

        $params = [
            'summit_id' =>  self::$summit->getId()
        ];

        $data = [
            'owner_first_name' => 'Sebastian',
            'owner_last_name' => 'Marcet',
            'owner_email' => 'smarcet@gmail.com',
            'ticket_type_id' => self::$ticketType->getId(),
            "owner_company" => "Pumant",
            //'promo_code' => 'STAFF'
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitOrdersApiController@add",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $order = json_decode($content);
        $this->assertTrue(!is_null($order));
        return $order;
    }

    public function testCreateSingleTicketOrderNotComplete(){
        $this->markTestSkipped('reserve endpoint is public (no auth.user middleware) but SagaFactory::build() requires non-null Member');

        $params = [
            'summit_id' =>  self::$summit->getId()
        ];

        $data = [
            'owner_first_name' => 'Sebastian',
            'owner_last_name' => 'Marcet',
            'owner_email' => 'smarcet@gmail.com',
            'ticket_type_id' => self::$ticketType->getId(),
            "owner_company" => "Pumant",
            //'promo_code' => 'STAFF'
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitOrdersApiController@add",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $order = json_decode($content);
        $this->assertTrue(!is_null($order));
        return $order;
    }

    /**
     * @param int $summit_id
     * @param int $order_id
     */
    public function testDeleteOrder(){
        $order = self::$summit->getOrders()->first();
        if(is_null($order)) $this->markTestSkipped('No orders in test data');

        $params = [
            'id' => self::$summit->getId(),
            'order_id' => $order->getId()
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "DELETE",
            "OAuth2SummitOrdersApiController@delete",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(204);
    }

    public function testGetAllOrders(){
        $params = [
            'id' => self::$summit->getId(),
            'page'     => 1,
            'per_page' => 10,
            'order'    => '+owner_name',
            'expand'   => 'tickets,tickets.owner',
            //'filter'   => 'status<>Cancelled,status<>Reserved',
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitOrdersApiController@getAllBySummit",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $orders = json_decode($content);
        $this->assertTrue(!is_null($orders));
        return $orders;
    }

    public function testGetTicketPdfByID(){
        $order = self::$summit->getOrders()->first();
        if(is_null($order)) $this->markTestSkipped('No orders in test data');
        $ticket = $order->getFirstTicket();
        if(is_null($ticket)) $this->markTestSkipped('No tickets in test data');

        $params = [
            'order_id'  => $order->getId(),
            'ticket_id' => $ticket->getId(),
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitOrdersApiController@getTicketPDFByOrderId",
            $params,
            [],
            [],
            [],
            $headers
        );

        $pdf_content = $response->getContent();
        $this->assertResponseStatus(200);
        $this->assertTrue(!is_null($pdf_content));
        return $pdf_content;
    }

    public function testUpdateTicketById(){
        $order = self::$summit->getOrders()->first();
        if(is_null($order)) $this->markTestSkipped('No orders in test data');
        $ticket = $order->getFirstTicket();
        if(is_null($ticket)) $this->markTestSkipped('No tickets in test data');

        $params = [
            'ticket_id' => $ticket->getId()
        ];

        $data = [
            'owner_first_name'    => 'Sebastian',
            'owner_last_name'     => 'Marcet',
            'owner_email'         => 'sebastian@marcet.com.ar',
            'disclaimer_accepted' => true,
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
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
        $this->assertResponseStatus(201);
        $order = json_decode($content);
        $this->assertTrue(!is_null($order));
        return $order;
    }

    public function testDoubleDelegate(){
        $order = self::$summit->getOrders()[0];
        $ticket = $order->getFirstTicket();

        $params = [
            'id' => self::$summit->getId(),
            'order_id' => $order->getId(),
            'ticket_id' =>  $ticket->getId(),
            'expand' => 'owner,owner.manager'
        ];

        $payload = [
            'attendee_first_name' => 'Joe',
            'attendee_last_name' => 'Doe',
        ];

        $response = $this->action(
            "PUT",
            "OAuth2SummitOrdersApiController@delegateTicket",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders(),
            json_encode($payload)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $ticket = json_decode($content);
        $this->assertTrue(!is_null($ticket));
        $this->assertTrue($ticket->owner->email === $ticket->owner->manager->email);

        $response = $this->action
        (
            "PUT",
            "OAuth2SummitOrdersApiController@delegateTicket",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders(),
            json_encode($payload)
        );

        $content = $response->getContent();
        // re-delegation to same attendee is now allowed (idempotent)
        $this->assertResponseStatus(201);
    }

    public function testDelegateTicket(){

        $order = self::$summit->getOrders()[0];
        $ticket = $order->getFirstTicket();

        $params = [
            'id' => self::$summit->getId(),
            'order_id' => $order->getId(),
            'ticket_id' =>  $ticket->getId(),
            'expand' => 'owner,owner.manager'
        ];

        $payload = [
           'attendee_first_name' => 'Joe',
           'attendee_last_name' => 'Doe',
        ];

        $response = $this->action(
            "PUT",
            "OAuth2SummitOrdersApiController@delegateTicket",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders(),
            json_encode($payload)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $ticket = json_decode($content);
        $this->assertTrue(!is_null($ticket));
        $this->assertTrue($ticket->owner->email === $ticket->owner->manager->email);

        // verify we can update the delegated ticket
        $params = [
            'ticket_id' =>  $ticket->id,
            'expand' => 'owner,owner.manager'
        ];

        $payload = [
            'attendee_first_name' => 'Joe2',
            'attendee_last_name' => 'Doe2',
        ];

        $response = $this->action(
            "PUT",
            "OAuth2SummitOrdersApiController@updateMyTicketById",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders(),
            json_encode($payload)
        );
        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $ticket = json_decode($content);
        $this->assertTrue(!is_null($ticket));
        return $ticket;
    }

    public function testDelegateAndUpdateEmail():void{
        $order = self::$summit->getOrders()[0];
        $ticket = $order->getFirstTicket();

        $params = [
            'id' => self::$summit->getId(),
            'order_id' => $order->getId(),
            'ticket_id' =>  $ticket->getId(),
            'expand' => 'owner,owner.manager'
        ];

        $payload = [
            'attendee_first_name' => 'Joe',
            'attendee_last_name' => 'Doe',
        ];

        $response = $this->action(
            "PUT",
            "OAuth2SummitOrdersApiController@delegateTicket",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders(),
            json_encode($payload)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $ticket = json_decode($content);
        $this->assertTrue(!is_null($ticket));
        $this->assertTrue($ticket->owner->email === $ticket->owner->manager->email);

        // update attendee from admin side

        $params = [
            'id' => self::$summit->getId(),
            'attendee_id' => $ticket->owner->id,
        ];

        $payload = [
            'email' => 'ihaveemailnowdady@gmail.com',
            'first_name' => 'Joe',
            'surname' => 'Doe',
        ];

        $response = $this->action(
            "PUT",
            "OAuth2SummitAttendeesApiController@updateAttendee",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders(),
            json_encode($payload)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $attendee = json_decode($content);
        $this->assertTrue($attendee->manager_id > 0);
        $this->assertTrue($attendee->email === 'ihaveemailnowdady@gmail.com');
    }
}