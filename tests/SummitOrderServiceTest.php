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
use App\Models\Foundation\ExtraQuestions\ExtraQuestionTypeConstants;
use App\Models\Foundation\ExtraQuestions\ExtraQuestionTypeValue;
use App\Models\Foundation\Main\IGroup;
use App\Models\Foundation\Summit\Registration\IBuildDefaultPaymentGatewayProfileStrategy;
use App\Models\Foundation\Summit\Repositories\ISummitAttendeeBadgePrintRuleRepository;
use App\Models\Foundation\Summit\Repositories\ISummitAttendeeBadgeRepository;
use App\Models\Foundation\Summit\Repositories\ISummitOrderRepository;
use App\Models\Foundation\Summit\Repositories\ISummitRefundRequestRepository;
use App\Services\FileSystem\IFileDownloadStrategy;
use App\Services\FileSystem\IFileUploadStrategy;
use App\Services\Model\ICompanyService;
use App\Services\Model\IMemberService;
use App\Services\Model\ISummitOrderService;
use App\Services\Model\Strategies\TicketFinder\ITicketFinderStrategyFactory;
use App\Services\Model\SummitOrderService;
use App\Services\Utils\ILockManagerService;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Queue;
use LaravelDoctrine\ORM\Facades\EntityManager;
use libs\utils\ITransactionService;
use Mockery;
use services\utils\AmbiguousCommitException;
use models\exceptions\EntityNotFoundException;
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
use models\summit\SummitAttendeeBadge;
use models\summit\SummitAttendeeTicket;
use models\summit\SummitBadgeFeatureType;
use models\summit\SummitBadgeType;
use models\summit\SummitOrder;
use models\summit\SummitOrderExtraQuestionAnswer;
use models\summit\SummitOrderExtraQuestionType;
use models\summit\SummitOrderExtraQuestionTypeConstants;
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
        $refund_request_repository = Mockery::mock(ISummitRefundRequestRepository::class)->makePartial();
        $company_service = Mockery::mock(ICompanyService::class);
        $ticket_finder_strategy_factory = Mockery::mock(ITicketFinderStrategyFactory::class);
        $member_reservation_repository = Mockery::mock(\models\summit\ISummitPromoCodeMemberReservationRepository::class);
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
        $ticket_repository->shouldReceive('getAllTicketsIdsByOrder')->andReturn([200, 201], []);
        $ticket_repository->shouldReceive('getById')->with(200)->andReturn($ticket);
        $ticket_repository->shouldReceive('getById')->with(201)->andReturn($ticket2);
        $summit_repository->shouldReceive("getByIdRefreshed")->andReturn($summit);
        $this->app->instance(ISummitRepository::class, $summit_repository);

        $service = new SummitOrderService(
            $ticket_type_repository,
            $member_repository,
            $promo_code_repository,
            $member_reservation_repository,
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
            $refund_request_repository,
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

    public function testAutoAssignPrePaidTicket() {

        $this->markTestSkipped('broken test.');
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

        $this->markTestSkipped('broken test.');
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

        $this->markTestSkipped('broken test.');
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

    /**
     * @param string $csv_content
     * @return ISummitOrderService
     */
    private function buildTicketDataImportService(
        string $csv_content,
        ?ITransactionService $tx_service = null,
        ?IFileDownloadStrategy $download_strategy = null
    ): ISummitOrderService
    {
        $upload_strategy = Mockery::mock(IFileUploadStrategy::class);

        if (is_null($download_strategy)) {
            $download_strategy = Mockery::mock(IFileDownloadStrategy::class);
            $download_strategy->shouldReceive('exists')->andReturn(true);
            $download_strategy->shouldReceive('get')->andReturn($csv_content);
            $download_strategy->shouldReceive('getDriver')->andReturn('mock');
            $download_strategy->shouldReceive('delete');
        }

        return new SummitOrderService(
            App::make(ISummitTicketTypeRepository::class),
            App::make(IMemberRepository::class),
            App::make(ISummitRegistrationPromoCodeRepository::class),
            App::make(\models\summit\ISummitPromoCodeMemberReservationRepository::class),
            App::make(ISummitAttendeeRepository::class),
            App::make(ISummitOrderRepository::class),
            App::make(ISummitAttendeeTicketRepository::class),
            App::make(ISummitAttendeeBadgeRepository::class),
            App::make(ISummitRepository::class),
            App::make(ISummitAttendeeBadgePrintRuleRepository::class),
            App::make(IMemberService::class),
            App::make(IBuildDefaultPaymentGatewayProfileStrategy::class),
            $upload_strategy,
            $download_strategy,
            App::make(ICompanyRepository::class),
            App::make(ITagRepository::class),
            App::make(ISummitRefundRequestRepository::class),
            App::make(ICompanyService::class),
            App::make(ITicketFinderStrategyFactory::class),
            $tx_service ?? App::make(ITransactionService::class),
            App::make(ILockManagerService::class)
        );
    }

    private function disableDefaultBadgeType(): int
    {
        $badge_type_id = self::$default_badge_type->getId();
        self::$default_badge_type->setIsDefault(false);
        return $badge_type_id;
    }

    private function restoreDefaultBadgeType(int $badge_type_id): void
    {
        // The failed transaction's rollback already cleared self::$em, detaching the
        // original self::$default_badge_type instance - restore via a fresh lookup so
        // the flush actually persists the flag, instead of silently no-op'ing on a
        // detached entity.
        self::$default_badge_type = self::$em->find(SummitBadgeType::class, $badge_type_id);
        self::$default_badge_type->setIsDefault(true);
        self::$em->flush();
    }

    /**
     * @param string $name
     * @param string $type
     * @param array $values
     * @return SummitOrderExtraQuestionType
     */
    private function insertOrderExtraQuestion
    (
        string $name,
        string $type = ExtraQuestionTypeConstants::TextQuestionType,
        array $values = [],
        string $usage = SummitOrderExtraQuestionTypeConstants::TicketQuestionUsage
    ): SummitOrderExtraQuestionType
    {
        $question = new SummitOrderExtraQuestionType();
        $question->setName($name);
        $question->setLabel($name);
        $question->setType($type);
        $question->setUsage($usage);

        foreach ($values as $value_name) {
            $value = new ExtraQuestionTypeValue();
            $value->setValue($value_name);
            $value->setLabel($value_name);
            $question->addValue($value);
        }

        self::$summit->addOrderExtraQuestion($question);
        self::$em->persist(self::$summit);
        self::$em->flush();

        return $question;
    }

    /**
     * @param SummitAttendee $attendee
     * @param SummitOrderExtraQuestionType $question
     * @param string $value
     */
    private function insertExtraQuestionAnswer(SummitAttendee $attendee, SummitOrderExtraQuestionType $question, string $value): void
    {
        $answer = new SummitOrderExtraQuestionAnswer();
        $answer->setQuestion($question);
        $answer->setValue($value);
        $attendee->addExtraQuestionAnswer($answer);
        self::$em->persist($attendee);
        self::$em->flush();
    }

    /**
     * @return SummitAttendeeTicket
     */
    private function getUnassignedTicket(): SummitAttendeeTicket
    {
        foreach (self::$summit->getOrders() as $order) {
            foreach ($order->getTickets() as $ticket) {
                if (!$ticket->hasOwner()) return $ticket;
            }
        }
        $this->fail('no unassigned ticket available on test fixture');
    }

    /**
     * @return SummitAttendee
     */
    private function getDefaultAttendee(): SummitAttendee
    {
        $attendee = App::make(ISummitAttendeeRepository::class)
            ->getBySummitAndEmail(self::$summit, self::$defaultMember->getEmail());
        $this->assertNotNull($attendee);
        return $attendee;
    }

    public function testImportTicketDataSetsExtraQuestionAnswerOnNewAttendee()
    {
        Queue::fake();

        $question = $this->insertOrderExtraQuestion('Dietary Requirements');
        $ticket = $this->getUnassignedTicket();

        $csv_content = <<<CSV
number,attendee_email,attendee_first_name,attendee_last_name,extra_question:Dietary Requirements
{$ticket->getNumber()},new.attendee@nowhere.com,New,Attendee,Vegan
CSV;

        $service = $this->buildTicketDataImportService($csv_content);
        $service->processTicketData(self::$summit->getId(), 'tickets.csv');

        $attendee = App::make(ISummitAttendeeRepository::class)
            ->getBySummitAndEmail(self::$summit, 'new.attendee@nowhere.com');

        $this->assertNotNull($attendee);
        $answer = $attendee->getExtraQuestionAnswerByQuestion($question);
        $this->assertNotNull($answer);
        $this->assertEquals('Vegan', $answer->getValue());
    }

    public function testImportTicketDataUpdatesExtraQuestionAnswerOnExistingAttendee()
    {
        Queue::fake();

        // background import runs without an authenticated admin, so answer updates
        // are gated by the summit level setting
        self::$summit->setAllowUpdateAttendeeExtraQuestions(true);
        self::$em->persist(self::$summit);
        self::$em->flush();

        $question = $this->insertOrderExtraQuestion('Dietary Requirements');
        $attendee = $this->getDefaultAttendee();

        // SummitAttendee::canChangeAnswerValue caches per attendee id for 60 secs,
        // drop any stale entry from a previous run ( attendee ids are reused across DB re-seeds )
        Cache::forget(sprintf("SummitAttendee.canChangeAnswerValue.%s", $attendee->getId()));
        $this->insertExtraQuestionAnswer($attendee, $question, 'Meat');

        $ticket = $attendee->getTickets()->first();

        $csv_content = <<<CSV
number,attendee_email,extra_question:Dietary Requirements
{$ticket->getNumber()},{$attendee->getEmail()},Vegan
CSV;

        $service = $this->buildTicketDataImportService($csv_content);
        $service->processTicketData(self::$summit->getId(), 'tickets.csv');

        $answer = $attendee->getExtraQuestionAnswerByQuestion($question);
        $this->assertNotNull($answer);
        $this->assertEquals('Vegan', $answer->getValue());
    }

    public function testImportTicketDataSkipsUnknownExtraQuestion()
    {
        Queue::fake();

        $this->insertOrderExtraQuestion('Dietary Requirements');
        $ticket = $this->getUnassignedTicket();

        $csv_content = <<<CSV
number,attendee_email,attendee_first_name,attendee_last_name,extra_question:Nonexistent Question
{$ticket->getNumber()},new.attendee@nowhere.com,New,Attendee,Some Value
CSV;

        $service = $this->buildTicketDataImportService($csv_content);
        $service->processTicketData(self::$summit->getId(), 'tickets.csv');

        // row is still processed ( attendee created and ticket assigned ), unknown question is skipped
        $attendee = App::make(ISummitAttendeeRepository::class)
            ->getBySummitAndEmail(self::$summit, 'new.attendee@nowhere.com');

        $this->assertNotNull($attendee);
        $this->assertCount(0, $attendee->getExtraQuestionAnswers());
    }

    public function testImportTicketDataIgnoresEmptyExtraQuestionValue()
    {
        Queue::fake();

        $question = $this->insertOrderExtraQuestion('Dietary Requirements');
        $attendee = $this->getDefaultAttendee();
        $this->insertExtraQuestionAnswer($attendee, $question, 'Vegan');

        $ticket = $attendee->getTickets()->first();

        $csv_content = <<<CSV
number,attendee_email,extra_question:Dietary Requirements
{$ticket->getNumber()},{$attendee->getEmail()},
CSV;

        $service = $this->buildTicketDataImportService($csv_content);
        $service->processTicketData(self::$summit->getId(), 'tickets.csv');

        // former answer is preserved, empty values never clear answers
        $answer = $attendee->getExtraQuestionAnswerByQuestion($question);
        $this->assertNotNull($answer);
        $this->assertEquals('Vegan', $answer->getValue());
    }

    public function testImportTicketDataListQuestionStoresValueIds()
    {
        Queue::fake();

        $question = $this->insertOrderExtraQuestion
        (
            'T-Shirt Size',
            ExtraQuestionTypeConstants::CheckBoxListQuestionType,
            ['Small', 'Large']
        );

        $ticket = $this->getUnassignedTicket();

        // reversed token order on purpose: stored value ids are normalized ( sorted )
        $csv_content = <<<CSV
number,attendee_email,attendee_first_name,attendee_last_name,extra_question:T-Shirt Size
{$ticket->getNumber()},new.attendee@nowhere.com,New,Attendee,Large|Small
CSV;

        $service = $this->buildTicketDataImportService($csv_content);
        $service->processTicketData(self::$summit->getId(), 'tickets.csv');

        $attendee = App::make(ISummitAttendeeRepository::class)
            ->getBySummitAndEmail(self::$summit, 'new.attendee@nowhere.com');

        $this->assertNotNull($attendee);
        $answer = $attendee->getExtraQuestionAnswerByQuestion($question);
        $this->assertNotNull($answer);

        $expected_value_ids = [
            $question->getValueByName('Small')->getId(),
            $question->getValueByName('Large')->getId(),
        ];
        sort($expected_value_ids);
        $this->assertEquals(implode(',', $expected_value_ids), $answer->getValue());
    }

    public function testImportTicketDataDuplicatedTokenOnSingleValueQuestionIsAccepted()
    {
        Queue::fake();

        $question = $this->insertOrderExtraQuestion
        (
            'Meal Preference',
            ExtraQuestionTypeConstants::RadioButtonListQuestionType,
            ['Vegan', 'Meat']
        );

        $ticket = $this->getUnassignedTicket();

        // duplicated token ( e.g. spreadsheet drag-fill artifact ): one distinct value selected,
        // must not be rejected by the single-value guard
        $csv_content = <<<CSV
number,attendee_email,attendee_first_name,attendee_last_name,extra_question:Meal Preference
{$ticket->getNumber()},new.attendee@nowhere.com,New,Attendee,Vegan|Vegan
CSV;

        $service = $this->buildTicketDataImportService($csv_content);
        $service->processTicketData(self::$summit->getId(), 'tickets.csv');

        $attendee = App::make(ISummitAttendeeRepository::class)
            ->getBySummitAndEmail(self::$summit, 'new.attendee@nowhere.com');
        $this->assertNotNull($attendee);

        $answer = $attendee->getExtraQuestionAnswerByQuestion($question);
        $this->assertNotNull($answer);
        $this->assertEquals(strval($question->getValueByName('Vegan')->getId()), $answer->getValue());
    }

    public function testImportTicketDataDuplicatedTokenOnCheckBoxListStoresUniqueValueIds()
    {
        Queue::fake();

        $question = $this->insertOrderExtraQuestion
        (
            'T-Shirt Size',
            ExtraQuestionTypeConstants::CheckBoxListQuestionType,
            ['Small', 'Large']
        );

        $ticket = $this->getUnassignedTicket();

        $csv_content = <<<CSV
number,attendee_email,attendee_first_name,attendee_last_name,extra_question:T-Shirt Size
{$ticket->getNumber()},new.attendee@nowhere.com,New,Attendee,Large|Large
CSV;

        $service = $this->buildTicketDataImportService($csv_content);
        $service->processTicketData(self::$summit->getId(), 'tickets.csv');

        $attendee = App::make(ISummitAttendeeRepository::class)
            ->getBySummitAndEmail(self::$summit, 'new.attendee@nowhere.com');
        $this->assertNotNull($attendee);

        $answer = $attendee->getExtraQuestionAnswerByQuestion($question);
        $this->assertNotNull($answer);
        // stored form is unique value ids: "5,5" would render duplicated labels ( getNiceValue )
        // and resist later correction under the answer-change lock ( "5,5" != "5" reads as a change )
        $this->assertEquals(strval($question->getValueByName('Large')->getId()), $answer->getValue());
    }

    public function testImportTicketDataBadgeFeaturesStillClearedAndReSet()
    {
        Queue::fake();

        $feature1 = new SummitBadgeFeatureType();
        $feature1->setName('FEATURE 1');
        self::$summit->addFeatureType($feature1);

        $feature2 = new SummitBadgeFeatureType();
        $feature2->setName('FEATURE 2');
        self::$summit->addFeatureType($feature2);

        $question = $this->insertOrderExtraQuestion('Dietary Requirements');

        // use an unassigned ticket: SummitTicketType::applyTo auto-creates a badge per ticket with
        // a DB-consistent one-to-one. The fixture's assigned tickets share a single badge entity
        // whose ticket FK can only point at one of them, and the import re-reads the ticket with
        // HINT_REFRESH ( getByNumberExclusiveLock ), so DB state is what the service sees.
        $ticket = $this->getUnassignedTicket();
        $ticket->getBadge()->addFeature($feature1);
        self::$em->persist(self::$summit);
        self::$em->flush();

        $csv_content = <<<CSV
number,attendee_email,attendee_first_name,attendee_last_name,badge_type_name,FEATURE 1,FEATURE 2,extra_question:Dietary Requirements
{$ticket->getNumber()},new.attendee@nowhere.com,New,Attendee,BADGE TYPE1,0,1,Vegan
CSV;

        $service = $this->buildTicketDataImportService($csv_content);
        $service->processTicketData(self::$summit->getId(), 'tickets.csv');

        // badge features are cleared and re set from the csv columns
        // ( re-read the badge from the ticket, the import may have refreshed the association )
        $feature_names = [];
        foreach ($ticket->getBadge()->getFeatures() as $feature) {
            $feature_names[] = $feature->getName();
        }
        $this->assertEquals(['FEATURE 2'], $feature_names);

        // extra question answer is set on the same row
        $attendee = App::make(ISummitAttendeeRepository::class)
            ->getBySummitAndEmail(self::$summit, 'new.attendee@nowhere.com');
        $this->assertNotNull($attendee);
        $answer = $attendee->getExtraQuestionAnswerByQuestion($question);
        $this->assertNotNull($answer);
        $this->assertEquals('Vegan', $answer->getValue());
    }

    public function testImportTicketDataBadgeFeatureRestrictedQuestionAnsweredInSameRowAsFeatureGrant()
    {
        Queue::fake();

        $feature = new SummitBadgeFeatureType();
        $feature->setName('VIP');
        self::$summit->addFeatureType($feature);

        $question = $this->insertOrderExtraQuestion('VIP Perk Choice');
        $question->addAllowedBadgeFeatureType($feature);
        self::$em->persist(self::$summit);
        self::$em->flush();

        // badge feature and the answer to a question restricted to that same feature,
        // both set on the same CSV row for a brand new attendee
        $ticket = $this->getUnassignedTicket();

        $csv_content = <<<CSV
number,attendee_email,attendee_first_name,attendee_last_name,badge_type_name,VIP,extra_question:VIP Perk Choice
{$ticket->getNumber()},new.attendee@nowhere.com,New,Attendee,BADGE TYPE1,1,Yes
CSV;

        $service = $this->buildTicketDataImportService($csv_content);
        $service->processTicketData(self::$summit->getId(), 'tickets.csv');

        // the badge feature is granted in this same row
        $feature_names = [];
        foreach ($ticket->getBadge()->getFeatures() as $f) {
            $feature_names[] = $f->getName();
        }
        $this->assertEquals(['VIP'], $feature_names);

        // ... so the question restricted to that same feature is answerable too
        $attendee = App::make(ISummitAttendeeRepository::class)
            ->getBySummitAndEmail(self::$summit, 'new.attendee@nowhere.com');
        $this->assertNotNull($attendee);
        $answer = $attendee->getExtraQuestionAnswerByQuestion($question);
        $this->assertNotNull($answer);
        $this->assertEquals('Yes', $answer->getValue());
    }

    public function testImportTicketDataBadgeFeatureRestrictedQuestionSameRowForExistingAttendee()
    {
        Queue::fake();

        $feature = new SummitBadgeFeatureType();
        $feature->setName('VIP');
        self::$summit->addFeatureType($feature);

        $question = $this->insertOrderExtraQuestion('VIP Perk Choice');
        $question->addAllowedBadgeFeatureType($feature);
        self::$em->persist(self::$summit);
        self::$em->flush();

        // existing attendee: the badge-features result cache gets warmed pre-grant earlier in the
        // same row ( updateStatus during the reassign path ), so this exercises the cache eviction
        $attendee = $this->getDefaultAttendee();
        $ticket = $attendee->getTickets()->first();

        $csv_content = <<<CSV
number,attendee_email,badge_type_name,VIP,extra_question:VIP Perk Choice
{$ticket->getNumber()},{$attendee->getEmail()},BADGE TYPE1,1,Yes
CSV;

        $service = $this->buildTicketDataImportService($csv_content);
        $service->processTicketData(self::$summit->getId(), 'tickets.csv');

        $answer = $attendee->getExtraQuestionAnswerByQuestion($question);
        $this->assertNotNull($answer);
        $this->assertEquals('Yes', $answer->getValue());
    }

    public function testImportTicketDataSkipsOrderScopedExtraQuestion()
    {
        Queue::fake();

        $question = $this->insertOrderExtraQuestion
        (
            'Billing Notes',
            ExtraQuestionTypeConstants::TextQuestionType,
            [],
            SummitOrderExtraQuestionTypeConstants::OrderQuestionUsage
        );

        $ticket = $this->getUnassignedTicket();

        $csv_content = <<<CSV
number,attendee_email,attendee_first_name,attendee_last_name,extra_question:Billing Notes
{$ticket->getNumber()},new.attendee@nowhere.com,New,Attendee,Some Value
CSV;

        $service = $this->buildTicketDataImportService($csv_content);
        $service->processTicketData(self::$summit->getId(), 'tickets.csv');

        // row still processes ( attendee created, ticket assigned ), order-scoped question is skipped
        $attendee = App::make(ISummitAttendeeRepository::class)
            ->getBySummitAndEmail(self::$summit, 'new.attendee@nowhere.com');
        $this->assertNotNull($attendee);
        $this->assertNull($attendee->getExtraQuestionAnswerByQuestion($question));
        $this->assertCount(0, $attendee->getExtraQuestionAnswers());
    }

    public function testImportTicketDataPreservesLockedAnswerWhenUpdatesDisallowed()
    {
        Queue::fake();

        // no authenticated admin during a queued import, so with the summit setting off
        // an existing non-empty answer can not be changed
        self::$summit->setAllowUpdateAttendeeExtraQuestions(false);
        self::$em->persist(self::$summit);
        self::$em->flush();

        $question = $this->insertOrderExtraQuestion('Dietary Requirements');
        $attendee = $this->getDefaultAttendee();
        $this->insertExtraQuestionAnswer($attendee, $question, 'Meat');

        Cache::forget(sprintf("SummitAttendee.canChangeAnswerValue.%s", $attendee->getId()));

        $ticket = $attendee->getTickets()->first();

        $csv_content = <<<CSV
number,attendee_email,extra_question:Dietary Requirements
{$ticket->getNumber()},{$attendee->getEmail()},Vegan
CSV;

        $service = $this->buildTicketDataImportService($csv_content);
        $service->processTicketData(self::$summit->getId(), 'tickets.csv');

        // the locked answer is preserved, the change is skipped
        $answer = $attendee->getExtraQuestionAnswerByQuestion($question);
        $this->assertNotNull($answer);
        $this->assertEquals('Meat', $answer->getValue());
    }

    public function testImportTicketDataCreatesBadgeWhenTicketHasNone()
    {
        Queue::fake();

        // on a summit with a default badge type a badge-less ticket can not exist:
        // SummitTicketType::getBadgeType falls back to the summit default, so applyTo
        // always builds a badge at setTicketType time. Use the second fixture summit
        // ( no badge types, hence no default ) — the real world case this covers.

        // the ticket-assignment email dispatched on the reassign path hard-requires a
        // support email on the summit; summit2's fixture does not set one
        self::$summit2->setSupportEmail('summit2@test.com');

        $badge_type = new SummitBadgeType();
        $badge_type->setName('VIP BADGE');
        $badge_type->setDescription('VIP BADGE');
        self::$summit2->addBadgeType($badge_type); // deliberately NOT the default

        $ticket_type = new SummitTicketType();
        $ticket_type->setName('NO BADGE TICKET TYPE');
        $ticket_type->setCost(100);
        $ticket_type->setCurrency('USD');
        $ticket_type->setQuantity2Sell(10);
        $ticket_type->setAudience(SummitTicketType::Audience_All);
        self::$summit2->addTicketType($ticket_type);

        $order = new SummitOrder();
        $order->setOwner(self::$defaultMember);
        $order->setSummit(self::$summit2);
        self::$summit2->addOrder($order);

        $ticket = new SummitAttendeeTicket();
        $ticket->setTicketType($ticket_type);
        $ticket->activate();
        $order->addTicket($ticket);
        $order->setPaid();
        $order->generateNumber();
        $ticket->generateNumber();
        $ticket->generateQRCode();

        self::$em->persist(self::$summit2);
        self::$em->flush();

        $this->assertFalse($ticket->hasBadge());

        $csv_content = <<<CSV
number,attendee_email,attendee_first_name,attendee_last_name,badge_type_name
{$ticket->getNumber()},new.attendee@nowhere.com,New,Attendee,VIP BADGE
CSV;

        $service = $this->buildTicketDataImportService($csv_content);
        $service->processTicketData(self::$summit2->getId(), 'tickets.csv');

        $this->assertTrue($ticket->hasBadge());
        $this->assertEquals('VIP BADGE', $ticket->getBadge()->getType()->getName());
    }

    public function testUpdateTicketReassignmentRegeneratesBadgeQRCode(){

        $attendee = self::$summit->getAttendeeByMember(self::$defaultMember);
        $this->assertNotNull($attendee);
        $ticket = $attendee->getTickets()->first();
        $this->assertNotNull($ticket);
        $this->assertTrue($ticket->hasBadge());
        $badge_id = $ticket->getBadge()->getId();

        $summit_id        = self::$summit->getId();
        $member2_email    = self::$member2->getEmail();
        $member2_first    = self::$member2->getFirstName();
        $member2_last     = self::$member2->getLastName();
        $member2_fullname = self::$member2->getFullName();
        $default_email    = self::$defaultMember->getEmail();

        // clear the identity map so the service performs a genuinely fresh load,
        // matching how a real HTTP request behaves.
        EntityManager::clear();

        $summit = EntityManager::getRepository(Summit::class)->find($summit_id);

        // the fixture (InsertSummitTestData) reuses one SummitAttendeeBadge PHP object
        // across several tickets, so only the LAST ticket it was attached to is the one
        // actually persisted as this badge's TicketID in the DB - resolve the real
        // ticket (and its order) via the badge's own association rather than trusting
        // attendee->getTickets()->first().
        $real_ticket = EntityManager::getRepository(SummitAttendeeBadge::class)->find($badge_id)->getTicket();
        $ticket_id   = $real_ticket->getId();
        $order_id    = $real_ticket->getOrder()->getId();

        $payload = [
            'attendee_email'      => $member2_email,
            'attendee_first_name' => $member2_first,
            'attendee_last_name'  => $member2_last,
        ];

        $service = App::make(ISummitOrderService::class);
        $service->updateTicket($summit, $order_id, $ticket_id, $payload);

        EntityManager::clear();
        $badge = EntityManager::getRepository(SummitAttendeeBadge::class)->find($badge_id);
        $qr_code = $badge->getQRCode();
        $this->assertNotEmpty($qr_code);
        $decoded = SummitAttendeeBadge::parseQRCode(SummitAttendeeBadge::decodeQRCodeFor($summit, $qr_code));

        $this->assertEquals($member2_email, $decoded['owner_email']);
        $this->assertEquals($member2_fullname, $decoded['owner_fullname']);
        $this->assertNotEquals($default_email, $decoded['owner_email']);
    }

    public function testUpdateTicketBadgeTypeChangeDoesNotDuplicateBadge(){

        $attendee = self::$summit->getAttendeeByMember(self::$defaultMember);
        $this->assertNotNull($attendee);
        $ticket = $attendee->getTickets()->first();
        $this->assertNotNull($ticket);
        $this->assertTrue($ticket->hasBadge());
        $badge_id = $ticket->getBadge()->getId();

        $new_badge_type = new SummitBadgeType();
        $new_badge_type->setName('NEW BADGE TYPE');
        $new_badge_type->setDescription('NEW BADGE TYPE DESCRIPTION');
        self::$summit->addBadgeType($new_badge_type);
        self::$em->persist(self::$summit);
        self::$em->flush();
        $new_badge_type_id = $new_badge_type->getId();

        $summit_id = self::$summit->getId();

        // see comment in testUpdateTicketReassignmentRegeneratesBadgeQRCode: resolve the
        // real ticket/order via the badge's own association, not collection order.
        EntityManager::clear();
        $summit      = EntityManager::getRepository(Summit::class)->find($summit_id);
        $real_ticket = EntityManager::getRepository(SummitAttendeeBadge::class)->find($badge_id)->getTicket();
        $ticket_id   = $real_ticket->getId();
        $ticket_number = $real_ticket->getNumber();
        $order_id    = $real_ticket->getOrder()->getId();

        $service = App::make(ISummitOrderService::class);
        $service->updateTicket($summit, $order_id, $ticket_id, ['badge_type_id' => $new_badge_type_id]);

        EntityManager::clear();

        // exactly one badge row for this ticket - the pre-existing badge was reused,
        // not shadowed by a second inserted row
        $badge_count = EntityManager::getRepository(SummitAttendeeBadge::class)
            ->createQueryBuilder('b')
            ->select('COUNT(b.id)')
            ->innerJoin('b.ticket', 't')
            ->where('t.number = :ticket_number')
            ->setParameter('ticket_number', $ticket_number)
            ->getQuery()
            ->getSingleScalarResult();
        $this->assertEquals(1, $badge_count);

        $badge = EntityManager::getRepository(SummitAttendeeBadge::class)->getBadgeByTicketNumber($ticket_number);
        $this->assertNotNull($badge);
        $this->assertEquals($badge_id, $badge->getId());
        $this->assertEquals('NEW BADGE TYPE', $badge->getType()->getName());
    }

    public function testAddTicketsCommitsAcrossNestedTransaction()
    {
        $service = App::make(ISummitOrderService::class);

        $order = self::$summit->getOrders()[0];
        $order_id = $order->getId();
        $initial_ticket_count = $order->getTickets()->count();

        $result = $service->addTickets(self::$summit, $order_id, [
            'ticket_type_id' => self::$default_ticket_type->getId(),
            'ticket_qty' => 2,
        ]);

        $this->assertEquals($initial_ticket_count + 2, $result->getTickets()->count());

        self::$em->clear();

        $reFetched = self::$em->find(SummitOrder::class, $order_id);
        $this->assertNotNull($reFetched);
        $this->assertEquals($initial_ticket_count + 2, $reFetched->getTickets()->count());
    }

    public function testCreateOfflineOrderCommitsAcrossNestedAndSequentialRootTransactions()
    {
        $service = App::make(ISummitOrderService::class);

        $owner_email = sprintf('offline-order-%s@test.com', uniqid());

        $payload = [
            'owner_email' => $owner_email,
            'owner_first_name' => 'Offline',
            'owner_last_name' => 'Buyer',
            'ticket_type_id' => self::$default_ticket_type->getId(),
            'ticket_qty' => 1,
        ];

        $order = $service->createOfflineOrder(self::$summit, $payload);
        $order_id = $order->getId();

        $this->assertTrue($order->isPaid());
        $this->assertEquals(1, $order->getTickets()->count());
        $ticket = $order->getFirstTicket();
        $this->assertNotNull($ticket);
        $owner = $ticket->getOwner();
        $this->assertNotNull($owner);
        $this->assertEquals($owner_email, $owner->getEmail());

        self::$em->clear();

        $reFetched = self::$em->find(SummitOrder::class, $order_id);
        $this->assertNotNull($reFetched);
        $this->assertTrue($reFetched->isPaid());
        $this->assertEquals(1, $reFetched->getTickets()->count());
        $reFetchedTicket = $reFetched->getFirstTicket();
        $this->assertNotNull($reFetchedTicket);
        $reFetchedOwner = $reFetchedTicket->getOwner();
        $this->assertNotNull($reFetchedOwner);
        $this->assertEquals($owner_email, $reFetchedOwner->getEmail());
    }

    public function testCreateTicketsForOrderRollsBackEntireChainOnInvalidPromoCode()
    {
        $service = App::make(ISummitOrderService::class);

        $order = self::$summit->getOrders()[0];
        $order_id = $order->getId();
        $initial_ticket_count = $order->getTickets()->count();
        $ticket_type_id = self::$default_ticket_type->getId();
        $initial_quantity_sold = self::$default_ticket_type->getQuantitySold();
        $bogus_promo_code = 'DOES-NOT-EXIST-' . uniqid();

        try {
            $service->addTickets(self::$summit, $order_id, [
                'ticket_type_id' => $ticket_type_id,
                'ticket_qty' => 1,
                'promo_code' => $bogus_promo_code,
            ]);
            $this->fail('Expected EntityNotFoundException was not thrown');
        } catch (EntityNotFoundException $ex) {
            $this->assertStringContainsString('Promo code', $ex->getMessage());
        }

        self::$em->clear();

        $reFetched = self::$em->find(SummitOrder::class, $order_id);
        $this->assertNotNull($reFetched);
        $this->assertEquals($initial_ticket_count, $reFetched->getTickets()->count());

        // proves the SummitTicketType::sell(1) mutation made before the promo-code
        // lookup threw was rolled back too, not just the order's ticket collection
        $reFetchedTicketType = self::$em->find(SummitTicketType::class, $ticket_type_id);
        $this->assertNotNull($reFetchedTicketType);
        $this->assertEquals($initial_quantity_sold, $reFetchedTicketType->getQuantitySold());
    }

    public function testCreateOfflineOrderRollsBackEntireChainOnMissingDefaultBadgeType()
    {
        $service = App::make(ISummitOrderService::class);
        $attendee_repository = App::make(ISummitAttendeeRepository::class);

        $badge_type_id = $this->disableDefaultBadgeType();

        $owner_email = sprintf('offline-order-badge-fail-%s@test.com', uniqid());

        $payload = [
            'owner_email' => $owner_email,
            'owner_first_name' => 'Offline',
            'owner_last_name' => 'BadgeFail',
            'ticket_type_id' => self::$default_ticket_type->getId(),
            'ticket_qty' => 1,
        ];

        try {
            $service->createOfflineOrder(self::$summit, $payload);
            $this->fail('Expected ValidationException was not thrown');
        } catch (ValidationException $ex) {
            $this->assertStringContainsString('default badge type', $ex->getMessage());
        } finally {
            $this->restoreDefaultBadgeType($badge_type_id);
        }

        self::$em->clear();

        $attendee = $attendee_repository->getBySummitAndEmail(self::$summit, $owner_email);
        $this->assertNull($attendee);
    }

    /**
     * processTicketData() must follow the importer's log-and-skip posture (skills/laravel-api.md
     * "CSV Import/Export Conventions" in the FN knowledge vault): a bad row is caught, logged, and
     * skipped, never aborts the rest of the file. Row 1 fails for a row-specific reason (its ticket
     * type has zero remaining capacity), independent of row 2, which uses a ticket type with normal
     * capacity. This proves both halves of the contract: the failing row doesn't propagate out of
     * processTicketData() (no exception), and it doesn't block row 2 from being attempted and
     * committed - not just that "nothing throws" for the same summit-wide reason.
     */
    public function testProcessTicketDataSkipsFailingRowsAndContinuesProcessingRemainingRows()
    {
        $attendee_repository = App::make(ISummitAttendeeRepository::class);

        $email1 = sprintf('csv-import-row1-%s@test.com', uniqid());
        $email2 = sprintf('csv-import-row2-%s@test.com', uniqid());

        $exhausted_ticket_type = new SummitTicketType();
        $exhausted_ticket_type->setName('SOLD OUT TICKET TYPE');
        $exhausted_ticket_type->setCost(100);
        $exhausted_ticket_type->setCurrency('USD');
        $exhausted_ticket_type->setQuantity2Sell(1);
        $exhausted_ticket_type->setAudience(SummitTicketType::Audience_All);
        self::$summit->addTicketType($exhausted_ticket_type);
        self::$em->persist(self::$summit);
        self::$em->flush();
        // consume the only seat so row 1's sell() call fails on capacity, not on a summit-wide toggle
        $exhausted_ticket_type->sell(1);
        self::$em->flush();

        $bad_ticket_type_id = $exhausted_ticket_type->getId();
        $good_ticket_type_id = self::$default_ticket_type->getId();

        $csv_content = <<<CSV
attendee_email,attendee_first_name,attendee_last_name,ticket_type_id
{$email1},CSV,RowOne,{$bad_ticket_type_id}
{$email2},CSV,RowTwo,{$good_ticket_type_id}
CSV;

        $service = $this->buildTicketDataImportService($csv_content);
        // must not throw: a bad row is logged and skipped, not fatal to the whole file
        $service->processTicketData(self::$summit->getId(), 'tickets.csv');

        self::$em->clear();

        $attendee1 = $attendee_repository->getBySummitAndEmail(self::$summit, $email1);
        $attendee2 = $attendee_repository->getBySummitAndEmail(self::$summit, $email2);
        $this->assertNull($attendee1);
        $this->assertNotNull($attendee2);
    }

    /**
     * Volume happy path: every row of a 20-row CSV is valid, so all 20 attendees
     * (each with its ticket) must exist afterwards and the source file must be
     * deleted - a fully-processed import leaves nothing to reconcile.
     */
    public function testProcessTicketDataImportsAllRowsWhenEveryRowIsValid()
    {
        $attendee_repository = App::make(ISummitAttendeeRepository::class);

        $bulk_type = new SummitTicketType();
        $bulk_type->setName('BULK OK TICKET TYPE');
        $bulk_type->setCost(100);
        $bulk_type->setCurrency('USD');
        $bulk_type->setQuantity2Sell(50);
        $bulk_type->setAudience(SummitTicketType::Audience_All);
        self::$summit->addTicketType($bulk_type);
        self::$em->persist(self::$summit);
        self::$em->flush();

        $uid = uniqid();
        $emails = [];
        $rows = [];
        for ($i = 1; $i <= 20; $i++) {
            $email = sprintf('csv-bulk-ok-%02d-%s@test.com', $i, $uid);
            $emails[] = $email;
            $rows[] = sprintf('%s,CSV,Row%02d,%d', $email, $i, $bulk_type->getId());
        }
        $csv_content = "attendee_email,attendee_first_name,attendee_last_name,ticket_type_id\n" . implode("\n", $rows);

        $file_deleted = false;
        $download_strategy = Mockery::mock(IFileDownloadStrategy::class);
        $download_strategy->shouldReceive('exists')->andReturn(true);
        $download_strategy->shouldReceive('get')->andReturn($csv_content);
        $download_strategy->shouldReceive('getDriver')->andReturn('mock');
        $download_strategy->shouldReceive('delete')->andReturnUsing(function () use (&$file_deleted) {
            $file_deleted = true;
        });

        $service = $this->buildTicketDataImportService($csv_content, null, $download_strategy);
        $service->processTicketData(self::$summit->getId(), 'tickets.csv');

        self::$em->clear();

        foreach ($emails as $email) {
            $attendee = $attendee_repository->getBySummitAndEmail(self::$summit, $email);
            $this->assertNotNull($attendee, sprintf('Attendee %s should have been imported', $email));
            $this->assertCount(1, $attendee->getTickets(), sprintf('Attendee %s should have exactly one ticket', $email));
        }
        $this->assertTrue($file_deleted, 'A fully-processed import must delete its source file');
    }

    /**
     * Volume mixed outcome: 20 rows where 15 fail (sold-out ticket type - sell()
     * throws, the row's transaction rolls back fully) interleaved with 5 valid
     * rows. The 5 valid rows must import with their tickets, the 15 failing rows
     * must leave no attendee behind, and the file is still deleted: a KNOWN
     * per-row failure (logged and skipped) is not an unknown outcome, so there
     * is nothing to reconcile against the DB.
     */
    public function testProcessTicketDataImportsOnlyValidRowsWhenMostRowsFail()
    {
        $attendee_repository = App::make(ISummitAttendeeRepository::class);

        $good_type = new SummitTicketType();
        $good_type->setName('BULK MIXED OK TICKET TYPE');
        $good_type->setCost(100);
        $good_type->setCurrency('USD');
        $good_type->setQuantity2Sell(50);
        $good_type->setAudience(SummitTicketType::Audience_All);
        self::$summit->addTicketType($good_type);

        $sold_out_type = new SummitTicketType();
        $sold_out_type->setName('BULK MIXED SOLD OUT TICKET TYPE');
        $sold_out_type->setCost(100);
        $sold_out_type->setCurrency('USD');
        $sold_out_type->setQuantity2Sell(1);
        $sold_out_type->setAudience(SummitTicketType::Audience_All);
        self::$summit->addTicketType($sold_out_type);
        self::$em->persist(self::$summit);
        self::$em->flush();
        // consume the only seat so every row using this type fails on capacity
        $sold_out_type->sell(1);
        self::$em->flush();

        $uid = uniqid();
        $good_emails = [];
        $bad_emails = [];
        $rows = [];
        for ($i = 1; $i <= 20; $i++) {
            // every 4th row is valid (positions 4, 8, 12, 16, 20): failures happen
            // both before and after each success, proving order independence
            $is_good = ($i % 4 === 0);
            $email = sprintf('csv-bulk-mix-%02d-%s@test.com', $i, $uid);
            if ($is_good) $good_emails[] = $email; else $bad_emails[] = $email;
            $rows[] = sprintf('%s,CSV,Row%02d,%d', $email, $i, $is_good ? $good_type->getId() : $sold_out_type->getId());
        }
        $csv_content = "attendee_email,attendee_first_name,attendee_last_name,ticket_type_id\n" . implode("\n", $rows);

        $file_deleted = false;
        $download_strategy = Mockery::mock(IFileDownloadStrategy::class);
        $download_strategy->shouldReceive('exists')->andReturn(true);
        $download_strategy->shouldReceive('get')->andReturn($csv_content);
        $download_strategy->shouldReceive('getDriver')->andReturn('mock');
        $download_strategy->shouldReceive('delete')->andReturnUsing(function () use (&$file_deleted) {
            $file_deleted = true;
        });

        $service = $this->buildTicketDataImportService($csv_content, null, $download_strategy);
        // must not throw: 15 known failures are logged and skipped
        $service->processTicketData(self::$summit->getId(), 'tickets.csv');

        self::$em->clear();

        $this->assertCount(5, $good_emails);
        $this->assertCount(15, $bad_emails);
        foreach ($good_emails as $email) {
            $attendee = $attendee_repository->getBySummitAndEmail(self::$summit, $email);
            $this->assertNotNull($attendee, sprintf('Valid row attendee %s should have been imported', $email));
            $this->assertCount(1, $attendee->getTickets(), sprintf('Attendee %s should have exactly one ticket', $email));
        }
        foreach ($bad_emails as $email) {
            $this->assertNull(
                $attendee_repository->getBySummitAndEmail(self::$summit, $email),
                sprintf('Failing row attendee %s should have been fully rolled back', $email)
            );
        }
        $this->assertTrue($file_deleted, 'Known per-row failures must not block the source file deletion');
    }

    /**
     * A row whose transaction surfaces AmbiguousCommitException has an UNKNOWN
     * outcome - it may or may not be durable (see DoctrineTransactionService) - so
     * the import must not treat it as either processed or failed: remaining rows
     * still run (they are independent), but the source file must NOT be deleted,
     * so the unknown-outcome row can be reconciled against the DB.
     */
    public function testProcessTicketDataKeepsFileAndContinuesWhenRowCommitOutcomeUnknown()
    {
        $attendee_repository = App::make(ISummitAttendeeRepository::class);

        $email1 = sprintf('csv-ambiguous-row1-%s@test.com', uniqid());
        $email2 = sprintf('csv-ambiguous-row2-%s@test.com', uniqid());
        $ticket_type_id = self::$default_ticket_type->getId();

        $csv_content = <<<CSV
attendee_email,attendee_first_name,attendee_last_name,ticket_type_id
{$email1},CSV,RowOne,{$ticket_type_id}
{$email2},CSV,RowTwo,{$ticket_type_id}
CSV;

        // transaction call #1 is the summit existence check; call #2 is row 1 -
        // simulate an ambiguous commit there (the callback never runs, mirroring a
        // commit whose outcome is unknown); every other call delegates for real.
        $tx_service = new class(App::make(ITransactionService::class)) implements ITransactionService {
            private $inner;
            private $calls = 0;
            public function __construct(ITransactionService $inner) { $this->inner = $inner; }
            public function transaction(\Closure $callback, int $isolationLevel = 2)
            {
                if (++$this->calls === 2)
                    throw new AmbiguousCommitException('commit outcome unknown (simulated)');
                return $this->inner->transaction($callback, $isolationLevel);
            }
        };

        $file_deleted = false;
        $download_strategy = Mockery::mock(IFileDownloadStrategy::class);
        $download_strategy->shouldReceive('exists')->andReturn(true);
        $download_strategy->shouldReceive('get')->andReturn($csv_content);
        $download_strategy->shouldReceive('getDriver')->andReturn('mock');
        $download_strategy->shouldReceive('delete')->andReturnUsing(function () use (&$file_deleted) {
            $file_deleted = true;
        });

        $service = $this->buildTicketDataImportService($csv_content, $tx_service, $download_strategy);
        // must not throw: the unknown-outcome row is recorded, not fatal to the file
        $service->processTicketData(self::$summit->getId(), 'tickets.csv');

        self::$em->clear();

        // row 2 was still processed - rows are independent
        $this->assertNotNull($attendee_repository->getBySummitAndEmail(self::$summit, $email2));
        // the source file survives so row 1 can be reconciled against the DB
        $this->assertFalse($file_deleted, 'The import file must be kept when any row has an unknown commit outcome');
    }

    public function testAddTicketsRollsBackEntireChainOnMissingDefaultBadgeType()
    {
        $service = App::make(ISummitOrderService::class);

        $order = self::$summit->getOrders()[0];
        $order_id = $order->getId();
        $initial_ticket_count = $order->getTickets()->count();

        $badge_type_id = $this->disableDefaultBadgeType();

        try {
            $service->addTickets(self::$summit, $order_id, [
                'ticket_type_id' => self::$default_ticket_type->getId(),
                'ticket_qty' => 1,
            ]);
            $this->fail('Expected ValidationException was not thrown');
        } catch (ValidationException $ex) {
            $this->assertStringContainsString('default badge type', $ex->getMessage());
        } finally {
            $this->restoreDefaultBadgeType($badge_type_id);
        }

        self::$em->clear();

        $reFetched = self::$em->find(SummitOrder::class, $order_id);
        $this->assertNotNull($reFetched);
        $this->assertEquals($initial_ticket_count, $reFetched->getTickets()->count());
    }

    public function testRequestRefundOrderRollsBackEntireChainWhenOneTicketIsFree()
    {
        Queue::fake();
        Config::set('registration.admin_email', 'admin@test.com');

        $service = App::make(ISummitOrderService::class);

        // Summit::canEmitRefundRequests() requires a future refund-request deadline;
        // InsertSummitTestData does not set one by default.
        self::$summit->setRegistrationAllowedRefundRequestTillDate(new \DateTime('+1 day', new \DateTimeZone('UTC')));

        $order = self::$summit->getOrders()[0];
        $order_id = $order->getId();
        $tickets = $order->getTickets();
        $this->assertGreaterThanOrEqual(2, $tickets->count());

        $refundableTicket = $tickets[0];
        $freeTicket = $tickets[1];
        $refundableTicketId = $refundableTicket->getId();
        $freeTicketId = $freeTicket->getId();

        // isPaid() is a pure status flag (set by SummitOrder::setPaid() on every ticket),
        // independent of cost - setRawCost(0.0) makes this ticket free without affecting
        // its already-paid status, so it still enters requestRefundOrder's loop.
        $freeTicket->setRawCost(0.0);

        // InsertSummitTestData sets the order's owner via SummitOrder::setOwner() only,
        // which does not populate Member's inverse summit_registration_orders collection;
        // requestRefundOrder() looks the order up via that collection with no self-healing
        // fallback (unlike updateMyOrder()), so add it explicitly.
        self::$defaultMember->addSummitRegistrationOrder($order);

        self::$em->persist($freeTicket);
        self::$em->persist(self::$defaultMember);
        self::$em->flush();

        try {
            $service->requestRefundOrder(self::$defaultMember, $order_id);
            $this->fail('Expected ValidationException was not thrown');
        } catch (ValidationException $ex) {
            $this->assertStringContainsString('free', $ex->getMessage());
        }

        self::$em->clear();

        $reFetchedRefundable = self::$em->find(SummitAttendeeTicket::class, $refundableTicketId);
        $reFetchedFree = self::$em->find(SummitAttendeeTicket::class, $freeTicketId);
        $this->assertNotNull($reFetchedRefundable);
        $this->assertNotNull($reFetchedFree);
        $this->assertEquals(0, $reFetchedRefundable->getRefundedRequests()->count());
        $this->assertEquals(0, $reFetchedFree->getRefundedRequests()->count());
    }
}
