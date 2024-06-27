<?php namespace Tests;
use App\Models\Foundation\Main\IGroup;
use LaravelDoctrine\ORM\Facades\EntityManager;
use models\summit\Summit;
use models\summit\SummitRegistrationInvitation;
use models\summit\SummitTicketType;

/**
 * Copyright 2018 OpenStack Foundation
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

/**
 * Class OAuth2TicketTypesApiTest
 * @package Tests
 */
final class OAuth2SummitTicketTypesApiTest extends ProtectedApiTest
{
    use InsertSummitTestData;

    use InsertOrdersTestData;

    use InsertMemberTestData;

    protected function setUp(): void
    {
        parent::setUp();
        self::insertSummitTestData();
        self::InsertOrdersTestData();
        self::insertMemberTestData(IGroup::TrackChairs);
    }

    protected function tearDown(): void
    {
        self::clearMemberTestData();
        self::clearSummitTestData();
        parent::tearDown();
    }

    public function testGetTicketTypes(){
        $params = [
            'id'       => self::$summit->getId(),
            'page'     => 1,
            'per_page' => 10,
            'order'    => '+name'
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitsTicketTypesApiController@getAllBySummit",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders()
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $ticket_types = json_decode($content);
        $this->assertTrue(!is_null($ticket_types));
        return $ticket_types;
    }

    public function testGetTicketTypesV2(){
        $params = [
            'id'       => self::$summit->getId(),
            'page'     => 1,
            'per_page' => 10,
            'order'    => '+name',
            'filter'   => 'audience==WithoutInvitation',
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitsTicketTypesApiController@getAllBySummitV2",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders()
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $ticket_types = json_decode($content);
        $this->assertTrue(!is_null($ticket_types));
        return $ticket_types;
    }

    public function testGetTicketTypesById(){
        $params = [
            'id'                => self::$summit->getId(),
            'ticket_type_id'    => self::$default_ticket_type->getId()
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitsTicketTypesApiController@getTicketTypeBySummit",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders()
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $ticket_type = json_decode($content);
        $this->assertTrue(!is_null($ticket_type));
        $this->assertTrue($ticket_type->id == self::$default_ticket_type->getId());
        return $ticket_type;
    }

    public function testGetAllowedTicketTypes(){
        $params = [
            'id'       => self::$summit->getId(),
            'page'     => 1,
            'per_page' => 10,
            'order'    => '+id'
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitsTicketTypesApiController@getAllowedBySummitAndCurrentMember",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders()
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $ticket_types = json_decode($content);
        $this->assertTrue(!is_null($ticket_types));
        return $ticket_types;
    }

    public function testGetAllowedTicketTypesApplyingPrePaidPromoCode(){

        $ticket = self::$summit_orders[0]->getFirstTicket();
        if (!is_null($ticket)) {
            $ticket->setPromoCode(self::$default_prepaid_discount_code);
            self::$default_prepaid_discount_code->addTicket($ticket);
            self::$em->persist(self::$default_prepaid_discount_code);
            self::$em->flush();
        }

        $params = [
            'id'       => self::$summit->getId(),
            'page'     => 1,
            'per_page' => 10,
            'order'    => '+id',
            'filter'   => 'promo_code==' . self::$default_prepaid_discount_code->getCode(),
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitsTicketTypesApiController@getAllowedBySummitAndCurrentMember",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders()
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $ticket_types = json_decode($content);
        $this->assertTrue(!is_null($ticket_types));
        $this->assertNotEmpty($ticket_types->data);
        $this->assertEquals(0, $ticket_types->data[0]->cost);
        $this->assertEquals(SummitTicketType::Subtype_PrePaid, $ticket_types->data[0]->sub_type);
        return $ticket_types;
    }

    public function testGetAllowedTicketTypesApplyingRegularDiscountCodeToSomeOfThem(){
        self::$default_discount_code->addAllowedTicketType(self::$default_ticket_type);
        self::$em->persist(self::$default_discount_code);
        self::$em->flush();

        $params = [
            'id'       => self::$summit->getId(),
            'page'     => 1,
            'per_page' => 10,
            'order'    => '+id',
            'filter'   => 'promo_code==' . self::$default_discount_code->getCode(),
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitsTicketTypesApiController@getAllowedBySummitAndCurrentMember",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders()
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $ticket_types = json_decode($content);
        $this->assertTrue(!is_null($ticket_types));

        $list_with_discount_applied = array_column($ticket_types->data, 'cost_with_applied_discount');

        $this->assertNotEmpty($list_with_discount_applied);
        $this->assertTrue(count($list_with_discount_applied) < count($ticket_types->data));

        return $ticket_types;
    }

    public function testGetAllowedTicketTypesApplyingRegularDiscountCodeToAllOfThem(){
        self::$default_discount_code->removeAllowedTicketType(self::$default_ticket_type);
        self::$default_discount_code->removeAllowedTicketType(self::$default_ticket_type_2);
        self::$default_discount_code->removeAllowedTicketType(self::$default_ticket_type_3);
        self::$em->persist(self::$default_discount_code);
        self::$em->flush();

        $params = [
            'id'       => self::$summit->getId(),
            'page'     => 1,
            'per_page' => 10,
            'order'    => '+id',
            'filter'   => 'promo_code==' . self::$default_discount_code->getCode(),
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitsTicketTypesApiController@getAllowedBySummitAndCurrentMember",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders()
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $ticket_types = json_decode($content);
        $this->assertTrue(!is_null($ticket_types));
        $this->assertNotEmpty($ticket_types->data);
        $this->assertLessThan($ticket_types->data[0]->cost, $ticket_types->data[0]->cost_with_applied_discount);
        $this->assertEquals(SummitTicketType::Subtype_Regular, $ticket_types->data[0]->sub_type);
        return $ticket_types;
    }

    public function testGetInvitationTicketTypesApplyingDiscountCode(){
        $invitation = new SummitRegistrationInvitation();
        $invitation->setFirstName(self::$member->getFirstName());
        $invitation->setLastName(self::$member->getLastName());
        $invitation->setEmail(self::$member->getEmail());
        $invitation->addTicketType(self::$default_ticket_type_3);
        self::$summit->addRegistrationInvitation($invitation);

        self::$default_discount_code->addAllowedTicketType(self::$default_ticket_type_3);
        self::$em->persist(self::$default_discount_code);
        self::$em->persist(self::$summit);
        self::$em->flush();

        $params = [
            'id'       => self::$summit->getId(),
            'page'     => 1,
            'per_page' => 10,
            'order'    => '+id',
            'filter'   => 'promo_code==' . self::$default_discount_code->getCode(),
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitsTicketTypesApiController@getAllowedBySummitAndCurrentMember",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders()
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $ticket_types = json_decode($content);
        $this->assertTrue(!is_null($ticket_types));
        $this->assertNotEmpty($ticket_types->data);
        $this->assertLessThan($ticket_types->data[0]->cost, $ticket_types->data[0]->cost_with_applied_discount);
        $this->assertEquals(SummitTicketType::Subtype_Regular, $ticket_types->data[0]->sub_type);
        return $ticket_types;
    }

    public function testAddTicketType(){
        $params = [
            'id' => self::$summit->getId(),
        ];

        $name        = str_random(16).'_ticket_type';
        $external_id = str_random(16).'_external_id';
        $audience    = SummitTicketType::Audience_All;

        $data = [
            'name'        => $name,
            'external_id' => $external_id,
            'audience'    => $audience,
        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitsTicketTypesApiController@addTicketTypeBySummit",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders(),
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $ticket_type = json_decode($content);
        $this->assertTrue(!is_null($ticket_type));
        $this->assertTrue($ticket_type->name == $name);
        $this->assertTrue($ticket_type->external_id == $external_id);
        $this->assertTrue($ticket_type->audience == $audience);
        return $ticket_type;
    }

    public function testUpdateTicketType(){
        $audience    = SummitTicketType::Audience_With_Invitation;

        $params = [
            'id'             => self::$summit->getId(),
            'ticket_type_id' => self::$default_ticket_type->getId()
        ];

        $data = [
            'description' => 'test description',
            'audience'    => $audience,
        ];

        $response = $this->action(
            "PUT",
            "OAuth2SummitsTicketTypesApiController@updateTicketTypeBySummit",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders(),
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $ticket_type = json_decode($content);
        $this->assertTrue(!is_null($ticket_type));
        $this->assertTrue($ticket_type->description == 'test description');
        $this->assertTrue($ticket_type->audience == $audience);
        return $ticket_type;
    }

    public function testSeedDefaultTicketTypes(){
        $params = [
            'id' => self::$summit->getId(),
        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitsTicketTypesApiController@seedDefaultTicketTypesBySummit",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders()
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $ticket_types = json_decode($content);
        $this->assertTrue(!is_null($ticket_types));
        return $ticket_types;
    }

    public function testUpdateTicketTypesCurrencySymbol(){
        $currency_symbol = SummitTicketType::EUR_Currency;

        $params = [
            'id'              => self::$summit->getId(),
            'currency_symbol' => $currency_symbol
        ];

        $this->action(
            "PUT",
            "OAuth2SummitsTicketTypesApiController@updateCurrencySymbol",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders()
        );

        $this->assertResponseStatus(201);
        $summit_repository = EntityManager::getRepository(Summit::class);
        $summit = $summit_repository->find(self::$summit->getId());
        $this->assertEquals($currency_symbol, $summit->getTicketTypes()->first()->getCurrency());
    }

     public function testUpdateTicketTypesCurrencySymbolAfterGlobalCurrencyUpdate(){
        $currency_symbol = SummitTicketType::EUR_Currency;

        $params = [
            'id'              => self::$summit->getId(),
            'currency_symbol' => $currency_symbol
        ];

        $headers = $this->getAuthHeaders();

        $this->action(
            "PUT",
            "OAuth2SummitsTicketTypesApiController@updateCurrencySymbol",
            $params,
            [],
            [],
            [],
            $headers
        );

        $this->assertResponseStatus(201);
        $summit_repository = EntityManager::getRepository(Summit::class);
        $summit = $summit_repository->find(self::$summit->getId());
        $this->assertEquals($currency_symbol, $summit->getTicketTypes()->first()->getCurrency());

        $params = [
            'id'             => self::$summit->getId(),
            'ticket_type_id' => self::$default_ticket_type->getId()
        ];

        $data = [
            'description'     => 'test description',
            'currency'        => $currency_symbol,
            'currency_symbol' => '€',
        ];

        $response = $this->action(
            "PUT",
            "OAuth2SummitsTicketTypesApiController@updateTicketTypeBySummit",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $ticket_type = json_decode($content);
        $this->assertTrue(!is_null($ticket_type));

        return $ticket_type;
    }
}