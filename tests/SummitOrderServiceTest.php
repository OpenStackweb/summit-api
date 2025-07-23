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

use App\Jobs\Emails\Registration\Reminders\SummitOrderReminderEmail;
use App\Jobs\Emails\Registration\Reminders\SummitTicketReminderEmail;
use App\Models\Foundation\Main\IGroup;
use App\Models\Foundation\Summit\Registration\IBuildDefaultPaymentGatewayProfileStrategy;
use App\Models\Foundation\Summit\Repositories\ISummitAttendeeBadgePrintRuleRepository;
use App\Models\Foundation\Summit\Repositories\ISummitAttendeeBadgeRepository;
use App\Models\Foundation\Summit\Repositories\ISummitOrderRepository;
use App\Services\FileSystem\IFileDownloadStrategy;
use App\Services\FileSystem\IFileUploadStrategy;
use App\Services\Model\ICompanyService;
use App\Services\Model\IMemberService;
use App\Services\Model\ISummitOrderService;
use App\Services\Model\Strategies\TicketFinder\ITicketFinderStrategyFactory;
use App\Services\Model\SummitOrderService;
use App\Services\Utils\ILockManagerService;
use Exception;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Queue;
use LaravelDoctrine\ORM\Facades\EntityManager;
use libs\utils\ITransactionService;
use Mockery;
use models\exceptions\ValidationException;
use models\main\ICompanyRepository;
use models\main\IMemberRepository;
use models\main\ITagRepository;
use models\summit\ISummitAttendeeRepository;
use models\summit\ISummitAttendeeTicketRepository;
use models\summit\ISummitRegistrationPromoCodeRepository;
use models\summit\ISummitRepository;
use models\summit\ISummitTicketTypeRepository;
use models\summit\Summit;
use models\summit\SummitAttendee;
use models\summit\SummitAttendeeTicket;
use models\summit\SummitOrder;
use models\summit\SummitRegistrationPromoCode;
use models\summit\SummitTicketType;

/**
 * Class SummitOrderServiceTest
 */
final class SummitOrderServiceTest extends BrowserKitTestCase
{
    use InsertSummitTestData;

    use InsertMemberTestData;

    protected function setUp(): void
    {
        parent::setUp();
        self::insertMemberTestData(IGroup::FoundationMembers);
        self::$defaultMember = self::$member;
        self::insertSummitTestData();
    }

    protected function tearDown(): void
    {
        self::clearSummitTestData();
        self::clearMemberTestData();
        parent::tearDown();
        Mockery::close();
    }

    public function testSendReminderOrdersEmail(){

        Queue::fake();

        $service = App::make(ISummitOrderService::class);

        list ($sent_orders_qty, $sent_tickets_qty) = $service->processSummitOrderReminders(self::$summit);

        $this->assertEquals(5, $sent_orders_qty);
        $this->assertEquals(1, $sent_tickets_qty);
        Queue::assertPushed(SummitOrderReminderEmail::class);
        Queue::assertPushed(SummitTicketReminderEmail::class);
    }

/*
    todo : fix these tests

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
*/

    public function testProcessSummitOrderRemindersReturnsEmptyIfSummitEnded()
    {
        $summit = Mockery::mock(Summit::class);
        $summit->shouldReceive('getId')->andReturn(123);
        $summit->shouldReceive('isEnded')->andReturn(true);

        $service = App::make(ISummitOrderService::class);

        $result = $service->processSummitOrderReminders($summit);

        $this->assertEquals([0, 0], $result);
    }

    public function testProcessSummitOrderRemindersSendsReminders()
    {
        Queue::fake();

        $ticket_type_repository = Mockery::mock(ISummitTicketTypeRepository::class);
        $member_repository = Mockery::mock(IMemberRepository::class);
        $promo_code_repository = Mockery::mock(ISummitRegistrationPromoCodeRepository::class);
        $attendee_repository = Mockery::mock(ISummitAttendeeRepository::class);
        $order_repository = Mockery::mock(ISummitOrderRepository::class);
        $ticket_repository = Mockery::mock(ISummitAttendeeTicketRepository::class);
        $badge_repository = Mockery::mock(ISummitAttendeeBadgeRepository::class);
        $summit_repository = Mockery::mock(ISummitRepository::class);
        $print_rules_repository = Mockery::mock(ISummitAttendeeBadgePrintRuleRepository::class);
        $member_service = Mockery::mock(IMemberService::class);
        $default_payment_gateway_strategy = Mockery::mock(IBuildDefaultPaymentGatewayProfileStrategy::class);
        $upload_strategy = Mockery::mock(IFileUploadStrategy::class);
        $download_strategy = Mockery::mock(IFileDownloadStrategy::class);
        $company_repository = Mockery::mock(ICompanyRepository::class);
        $tags_repository = Mockery::mock(ITagRepository::class);
        $company_service = Mockery::mock(ICompanyService::class);
        $ticket_finder_strategy_factory = Mockery::mock(ITicketFinderStrategyFactory::class);
        $tx_service = Mockery::mock(ITransactionService::class);
        $lock_service = Mockery::mock(ILockManagerService::class);

        $summit = Mockery::mock(Summit::class);
        $summit->shouldReceive('getId')->andReturn(1)->atLeast()->once();
        $summit->shouldReceive('isEnded')->andReturn(false)->atLeast()->once();
        $summit->shouldReceive('getRegistrationReminderEmailDaysInterval')->andReturn(3)->atLeast()->once();
        $summit->shouldReceive('getReassignTicketTillDateLocal')->andReturn(new \DateTime('10 days'))->atLeast()->once();
        $summit->shouldReceive("getSupportEmail")->andReturn("summit@test.com");
        $summit->shouldReceive("getVirtualSiteOAuth2ClientId")->andReturn("123");
        $summit->shouldReceive("getMarketingSiteOAuth2ClientId")->andReturn("123");
        $summit->shouldReceive("getMarketingSiteOauth2ClientScopes")->andReturn("scope");
        $summit->shouldReceive("getEmailIdentifierPerEmailEventFlowSlug")->andReturn("TEMPLATE_ID");
        $summit->shouldReceive("getName")->andReturn("TEST SUMMIT");
        $summit->shouldReceive('getLogoUrl')->andReturn('https://example.com/logo.png');
        $summit->shouldReceive('getVirtualSiteUrl')->andReturn('https://virtual.example.com');
        $summit->shouldReceive('getMarketingSiteUrl')->andReturn('https://marketing.example.com');
        $summit->shouldReceive('getLocalBeginDate')->andReturn(new \DateTime('2025-09-01'));
        $summit->shouldReceive('getDatesLabel')->andReturn('September 1-5, 2025');
        $summit->shouldReceive('getScheduleDefaultPageUrl')->andReturn('https://schedule.example.com');
        $summit->shouldReceive('getLink')->andReturn('https://summit.example.com');
        $summit->shouldReceive('getRegistrationLink')->andReturn('https://register.example.com');
        $summit->shouldReceive("getMainVenues")->andReturn([]);

        $ticket_type = Mockery::mock(SummitTicketType::class);
        $ticket_type->shouldReceive("getName")->andReturn("TICKET TYPE 1");

        $order = Mockery::mock(SummitOrder::class);
        $order->shouldReceive('getId')->andReturn(100)->atLeast()->once();

        $order->shouldReceive('getSummit')->andReturn($summit);
        $order->shouldReceive('isPaid')->andReturn(true);
        $order->shouldReceive('getOwnerEmail')->andReturn('foo@example.org');
        $order->shouldReceive('getLastReminderEmailSentDate')->andReturn(new \DateTime('-10 days'));
        $order->shouldReceive('updateLastReminderEmailSentDate')->once();
        $order->shouldReceive("getOwnerFullName")->andReturn('John Doe');
        $order->shouldReceive("getOwnerCompanyName")->andReturn('FNTECH');

        $attendee = Mockery::mock(SummitAttendee::class);
        $attendee->shouldReceive('isComplete')->andReturn(false);
        $attendee->shouldReceive('getLastReminderEmailSentDate')->andReturn(new \DateTime('-10 days'));
        $attendee->shouldReceive('updateLastReminderEmailSentDate')->once();
        $attendee->shouldReceive('getSummit')->andReturn($summit);
        $attendee->shouldReceive("getFirstName")->andReturn('John');
        $attendee->shouldReceive("getSurname")->andReturn('Doe');
        $attendee->shouldReceive("getCompanyName")->andReturn('FNTECH');
        $attendee->shouldReceive("getEmail")->andReturn('john@doe.com');
        $attendee->shouldReceive("getFullName")->andReturn('John Doe');
        $attendee->shouldReceive("needToFillDetails")->andReturn(true);

        $ticket = Mockery::mock(SummitAttendeeTicket::class);
        $ticket->shouldReceive('getId')->andReturn(200);
        $ticket->shouldReceive('isActive')->andReturn(true);
        $ticket->shouldReceive('hasOwner')->andReturn(true);
        $ticket->shouldReceive('isPaid')->andReturn(true);
        $ticket->shouldReceive('hasTicketType')->andReturn(true);
        $ticket->shouldReceive('getOwner')->andReturn($attendee);
        $ticket->shouldReceive('generateHash')->once();
        $ticket->shouldReceive('getOrder')->andReturn($order);
        $ticket->shouldReceive("getNumber")->andReturn("TICKET_NUMBER1");
        $ticket->shouldReceive("getTicketType")->andReturn($ticket_type);
        $ticket->shouldReceive("getFinalAmount")->andReturn(0.0);
        $ticket->shouldReceive("getCurrency")->andReturn("USD");
        $ticket->shouldReceive("getCurrencySymbol")->andReturn("$");
        $ticket->shouldReceive("hasPromoCode")->andReturn(false);

        $ticket2 = Mockery::mock(SummitAttendeeTicket::class);
        $ticket2->shouldReceive('getId')->andReturn(201);
        $ticket2->shouldReceive('isActive')->andReturn(true);
        $ticket2->shouldReceive('hasOwner')->andReturn(false);
        $ticket2->shouldReceive('isPaid')->andReturn(true);
        $ticket2->shouldReceive('hasTicketType')->andReturn(true);
        $ticket2->shouldReceive('getOwner')->andReturn(null);
        $ticket2->shouldReceive('generateHash')->once();
        $ticket2->shouldReceive('getOrder')->andReturn($order);
        $ticket2->shouldReceive("getNumber")->andReturn("TICKET_NUMBER2");
        $ticket2->shouldReceive("getTicketType")->andReturn($ticket_type);
        $ticket2->shouldReceive("getFinalAmount")->andReturn(0.0);
        $ticket2->shouldReceive("getCurrency")->andReturn("USD");
        $ticket2->shouldReceive("getCurrencySymbol")->andReturn("$");
        $ticket2->shouldReceive("hasPromoCode")->andReturn(false);

        $order->shouldReceive('getTickets')->andReturn(new \Doctrine\Common\Collections\ArrayCollection([$ticket, $ticket2]));

        $tx_service->shouldReceive('transaction')->andReturnUsing(function ($callback) {
            return $callback();
        });

        $order_repository->shouldReceive('getAllOrderIdsThatNeedsEmailActionReminder')->andReturn([100], []);
        $order_repository->shouldReceive('getById')->andReturn($order);
        $ticket_repository->shouldReceive('getAllTicketsIdsByOrder')->andReturn([200], [201]);
        $ticket_repository->shouldReceive('getById')->andReturn($ticket);
        $summit_repository->shouldReceive("getByIdRefreshed")->andReturn($summit);
        $this->app->instance(ISummitRepository::class, $summit_repository);

        $service = new SummitOrderService(
            $ticket_type_repository,
            $member_repository,
            $promo_code_repository,
            $attendee_repository,
            $order_repository,
            $ticket_repository,
            $badge_repository,
            $summit_repository,
            $print_rules_repository,
            $member_service,
            $default_payment_gateway_strategy,
            $upload_strategy,
            $download_strategy,
            $company_repository,
            $tags_repository,
            $company_service,
            $ticket_finder_strategy_factory,
            $tx_service,
            $lock_service
        );

        $result = $service->processSummitOrderReminders($summit);

        $this->assertEquals([1, 1], $result);

        Queue::assertPushed(SummitOrderReminderEmail::class);
        Queue::assertPushed(SummitTicketReminderEmail::class);
    }

}