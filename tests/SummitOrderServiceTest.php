<?php namespace Tests;
/**
 * Copyright 2022 OpenStack Foundation
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
use App\Services\Model\ISummitOrderService;
use Exception;
use Illuminate\Support\Facades\App;
use LaravelDoctrine\ORM\Facades\EntityManager;
use LaravelDoctrine\ORM\Facades\Registry;
use models\exceptions\ValidationException;
use models\oauth2\IResourceServerContext;
use models\summit\Summit;
use models\summit\SummitOrder;
use models\summit\SummitRegistrationPromoCode;
use models\utils\SilverstripeBaseModel;

/**
 * Class SummitOrderServiceTest
 */
final class SummitOrderServiceTest extends TestCase
{
    use InsertSummitTestData;

    use InsertOrdersTestData;

    use InsertMemberTestData;

    protected function setUp(): void
    {
        parent::setUp();

//        self::insertMemberTestData(IGroup::TrackChairs);
//        self::$defaultMember = self::$member;
//        self::insertSummitTestData();
//        self::InsertOrdersTestData();
//
        self::$em = Registry::getManager(SilverstripeBaseModel::EntityManager);
        if (!self::$em ->isOpen()) {
            self::$em = Registry::resetManager(SilverstripeBaseModel::EntityManager);
        }

        $ctx = App::make(IResourceServerContext::class);
        if(!$ctx instanceof IResourceServerContext)
            throw new \Exception();

        $context = [];
        $context['user_id'] = "1080";
        $context['external_user_id'] = "1080";
        $context['user_identifier']  = "test";
        $context['user_email']       = "test@test.com";
        $context['user_email_verified'] = true;
        $context['user_first_name']  = "test";
        $context['user_last_name']   = "test";
        $context['user_groups']      = ['raw-users'];
        $ctx->setAuthorizationContext($context);
    }

    protected function tearDown(): void
    {
//        self::clearMemberTestData();
//        self::clearSummitTestData();
        parent::tearDown();
    }

    public function testProcessSummitOrderReminders() {

        $service = App::make(ISummitOrderService::class);
        $service->processSummitOrderReminders(self::$summit);
    }

    public function testAutoAssignPrePaidTicket() {

        $order = self::$summit->getOrders()[0];
        $owner = $order->getOwner();

        $service = App::make(ISummitOrderService::class);

        $payload = [
            "owner_email"      => $owner->getEmail(),
            "owner_first_name" => $owner->getFirstName(),
            "owner_last_name"  => $owner->getLastName(),
            "owner_company"    => $owner->getCompany(),
            "tickets" => [
                [
                    "type_id"    => self::$summit->getTicketTypes()[0]->getId(),
                    "promo_code" => self::$default_prepaid_discount_code->getCode()
                ],
            ]
        ];

        $order = $service->reserve($owner, self::$summit, $payload);
        $this->assertNotNull($order);
    }

    public function testAutoAssignPrePaidTicketWithoutAvailableTickets() {

        $order = self::$summit->getOrders()[0];
        $owner = $order->getOwner();

        self::$default_prepaid_discount_code->clearTickets();
        self::$em->persist(self::$default_prepaid_discount_code);
        self::$em->flush();

        $service = App::make(ISummitOrderService::class);

        $payload = [
            "owner_email"      => $owner->getEmail(),
            "owner_first_name" => $owner->getFirstName(),
            "owner_last_name"  => $owner->getLastName(),
            "owner_company"    => $owner->getCompany(),
            "tickets" => [
                [
                    "type_id"    => self::$summit->getTicketTypes()[0]->getId(),
                    "promo_code" => self::$default_prepaid_discount_code->getCode()
                ],
            ]
        ];

        try {
            $service->reserve($owner, self::$summit, $payload);
        } catch (Exception $ex) {
            $this->assertInstanceOf(ValidationException::class, $ex);
            $this->assertTrue(str_starts_with($ex->getMessage(), 'No more available PrePaid Tickets for Promo Code'));
        }
    }

    public function testAutoAssignDifferentPrePaidTicketsUntilEmpty() {

        $summit_repository = EntityManager::getRepository(Summit::class);
        $pc_repository = EntityManager::getRepository(SummitRegistrationPromoCode::class);
        self::$summit = $summit_repository->find(3800);
        self::$default_prepaid_discount_code = $pc_repository->find(488);



        $order1 = self::$summit->getOrders()[0];
        $ticket1 = $order1->getTickets()[0];
        $owner1 = $order1->getOwner();
        $ticket_type1 = $ticket1->getTicketType();

        $service = App::make(ISummitOrderService::class);

        $payload1 = [
            "owner_email"      => $owner1->getEmail(),
            "owner_first_name" => $owner1->getFirstName(),
            "owner_last_name"  => $owner1->getLastName(),
            "owner_company"    => $owner1->getCompany(),
            "tickets" => [
                [
                    "type_id"    => $ticket_type1->getId(),
                    "promo_code" => self::$default_prepaid_discount_code->getCode()
                ],
            ]
        ];

        $order2 = self::$summit->getOrders()[1];
        $ticket2 = $order2->getTickets()[0];
        $owner2 = $order2->getOwner();
        $ticket_type2 = $ticket2->getTicketType();

        $payload2 = [
            "owner_email"      => $owner2->getEmail(),
            "owner_first_name" => $owner2->getFirstName(),
            "owner_last_name"  => $owner2->getLastName(),
            "owner_company"    => $owner2->getCompany(),
            "tickets" => [
                [
                    "type_id"    => $ticket_type2->getId(),
                    "promo_code" => self::$default_prepaid_discount_code->getCode()
                ],
            ]
        ];

        self::$default_prepaid_discount_code->clearTickets();
        self::$em->persist(self::$default_prepaid_discount_code);

        self::$default_prepaid_discount_code->addTicket($ticket1);
        self::$default_prepaid_discount_code->addTicket($ticket2);
        self::$em->flush();

        try {
            $order1 = $service->reserve($owner1, self::$summit, $payload1);
            $order2 = $service->reserve($owner2, self::$summit, $payload2);

            $this->assertTrue($order1->getTickets()->first()->getId() != $order2->getTickets()->first()->getId());

            $service->reserve($owner1, self::$summit, $payload1);
        } catch (Exception $ex) {
            $this->assertInstanceOf(ValidationException::class, $ex);
            $this->assertTrue(str_starts_with($ex->getMessage(), 'No more available PrePaid Tickets for Promo Code'));
        }
    }
}