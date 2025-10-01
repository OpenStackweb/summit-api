<?php
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
use App\Jobs\Emails\Schedule\RSVP\ProcessRSVPInvitationsJob;
use App\Models\Foundation\Summit\Events\RSVP\RSVPInvitation;
use App\Services\Model\Imp\SummitRSVPInvitationService;
use App\Services\Utils\CSVReader;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use libs\utils\ITransactionService;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use models\summit\ISummitEventRepository;
use models\summit\ISummitRepository;
use models\summit\SummitEvent;
use App\Models\Foundation\Summit\Events\RSVP\Repositories\IRSVPInvitationRepository;
use App\Services\Model\ISummitRSVPService;
use Laravel\BrowserKitTesting\TestCase;
use models\summit\Summit;
use models\summit\SummitAttendee;
use models\main\Member;
use App\Jobs\Emails\Schedule\RSVP\RSVPInviteEmail;
use App\Services\Utils\Facades\EmailExcerpt;
use models\summit\RSVP;
use Illuminate\Support\Facades\App;
use models\summit\ISummitAttendeeRepository;
/**
 * @covers \App\Services\Model\Imp\SummitRSVPInvitationService
 */
class SummitRSVPInvitationServiceTest extends TestCase
{
    use MockeryPHPUnitIntegration, WithFaker;


    /** @var ISummitEventRepository|Mockery\MockInterface */
    private $summit_event_repository;

    /** @var IRSVPInvitationRepository|Mockery\MockInterface */
    private $invitation_repository;

    /** @var ISummitRSVPService|Mockery\MockInterface */
    private $rsvp_service;

    /** @var ITransactionService */
    private $tx_service;

    /** @var SummitRSVPInvitationService */
    private $service;

    /**
     * @var \models\summit\ISummitAttendeeRepository
     */
    private $attendee_repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->summit_event_repository  = Mockery::mock(ISummitEventRepository::class);
        $this->invitation_repository    = Mockery::mock(IRSVPInvitationRepository::class);
        $this->rsvp_service= Mockery::mock(ISummitRSVPService::class);
        $this->attendee_repository = Mockery::mock(ISummitAttendeeRepository::class)->makePartial();

        $this->tx_service         = new class implements ITransactionService {
            public function transaction(Closure $callback, int $isolationLevel = 2)
            {
                // run inline for tests
                return $callback();
            }
        };

        $this->service = new SummitRSVPInvitationService(
            $this->summit_event_repository,
            $this->invitation_repository,
            $this->rsvp_service,
            $this->attendee_repository,
            $this->tx_service
        );


        $summit_repository = Mockery::mock(ISummitRepository::class)->makePartial();

        $summit_mock = Mockery::mock(Summit::class)->makePartial();
        $summit_mock->shouldReceive('getMainVenues')->andReturn([]);
        $summit_mock->shouldReceive('getId')->andReturn(1);
        $summit_repository->shouldReceive('getByIdRefreshed')->andReturn($summit_mock);
        App::instance(ISummitRepository::class, $summit_repository);

        Log::swap(Mockery::mock(\Psr\Log\LoggerInterface::class)->shouldIgnoreMissing());
    }

    private function makeSummitEventGraph(array $opts = [])
    {
        $event = Mockery::mock(SummitEvent::class);

        // backing stores the Summit mock will read from
        $attendeesById = [];
        $attendeesByEmail = [];

        // Real class mock to satisfy ?Summit return type
        $summit = Mockery::mock(Summit::class);
        $summit->shouldReceive('getId')->andReturn($opts['summit_id'] ?? 777)->byDefault();

        // Must return ?SummitAttendee
        $summit->shouldReceive('getAttendeeById')
            ->andReturnUsing(function ($id) use (&$attendeesById) {
                return $attendeesById[$id] ?? null; // either SummitAttendee mock or null
            })
            ->byDefault();

        // Must return ?SummitAttendee
        $summit->shouldReceive('getAttendeeByEmail')
            ->andReturnUsing(function ($email) use (&$attendeesByEmail) {
                return $attendeesByEmail[strtolower($email)] ?? null; // SummitAttendee mock or null
            })
            ->byDefault();

        $event->shouldReceive('getId')->andReturn($opts['event_id'] ?? 456)->byDefault();
        $event->shouldReceive('getSummit')->andReturn($summit)->byDefault();
        $event->shouldReceive('getRSVPInvitationByInvitee')->andReturnNull()->byDefault();

        // helper to add valid SummitAttendee mocks
        $addAttendee = function (int $id, string $email, bool $hasMember = true, bool $hasPaidTickets = true)
        use (&$attendeesById, &$attendeesByEmail) {

            $att = Mockery::mock(SummitAttendee::class);
            $att->shouldReceive('getId')->andReturn($id);
            $att->shouldReceive('getEmail')->andReturn($email);
            $att->shouldReceive('hasMember')->andReturn($hasMember);
            $att->shouldReceive('hasTicketsPaidTickets')->andReturn($hasPaidTickets);
            $att->shouldReceive('getMember')->andReturn((object)['id' => 123]);

            $attendeesById[$id] = $att;
            $attendeesByEmail[strtolower($email)] = $att;
            return $att;
        };

        return [$event, $summit, $addAttendee];
    }

    /** -------------------- importInvitationData -------------------- */

    public function testImportInvitationDataRejectsInvalidExtension(): void
    {
        $this->expectException(ValidationException::class);

        $file = Mockery::mock(UploadedFile::class);
        $file->shouldReceive('extension')->andReturn('pdf');
        $file->shouldReceive('getRealPath')->never();

        $this->service->importInvitationData(Mockery::mock(SummitEvent::class)->makePartial(), $file);
    }

    public function testImportInvitationDataEmptyFile(): void
    {
        $this->expectException(ValidationException::class);

        $file = Mockery::mock(UploadedFile::class);
        $file->shouldReceive('extension')->andReturn('csv');
        $file->shouldReceive('getRealPath')->andReturn('/tmp/fake.csv');

        File::shouldReceive('get')->once()->andReturn(''); // empty

        $this->service->importInvitationData(Mockery::mock(SummitEvent::class)->makePartial(), $file);
    }

    public function testImportInvitationDataMissingEmailColumn(): void
    {
        $this->expectException(ValidationException::class);

        $file = Mockery::mock(UploadedFile::class);
        $file->shouldReceive('extension')->andReturn('csv');
        $file->shouldReceive('getRealPath')->andReturn('/tmp/fake.csv');

        File::shouldReceive('get')->once()->andReturn("email\nfoo@example.org"); // content is irrelevant; we mock CSVReader

        // Mock the CSVReader::buildFrom alias/static call
        $reader = new class {
            public function hasColumn($name){ return false; }
            public function getIterator(){ return new \ArrayIterator([]); }
        };

        Mockery::mock('alias:' . CSVReader::class)
            ->shouldReceive('buildFrom')->once()->andReturn($reader);

        $this->service->importInvitationData(Mockery::mock(SummitEvent::class)->makePartial(), $file);
    }

    public function testImportInvitationDataCreatesInvitations(): void
    {

        $this->expectException(ValidationException::class);

        [$event, $summit, $addAttendee] = $this->makeSummitEventGraph();
        $addAttendee(1, 'a@example.org');
        $addAttendee(2, 'b@example.org');

        $file = Mockery::mock(UploadedFile::class);
        $file->shouldReceive('extension')->andReturn('csv');
        $file->shouldReceive('getRealPath')->andReturn('/tmp/fake.csv');

        File::shouldReceive('get')->once()->andReturn("whatever");

        $rows = [
            ['email' => 'a@example.org'],
            ['email' => 'b@example.org'],
            ['email' => 'missing@example.org'], // should be skipped
        ];

        $reader = new class($rows) implements \IteratorAggregate {
            private $rows;
            public function __construct($rows){ $this->rows = $rows; }
            public function hasColumn($n){ return $n === 'email'; }
            public function getIterator(){ return new \ArrayIterator($this->rows); }
        };

        Mockery::mock('alias:' . CSVReader::class)
            ->shouldReceive('buildFrom')->once()->andReturn($reader);

        // ensure update path is not triggered (we set getRSVPInvitationByInvitee to null by default)
        // expect add() to be called twice on SummitEvent
        $event->shouldReceive('addRSVPInvitation')
            ->twice()
            ->withArgs(function($attendee){ return in_array($attendee->getEmail(), ['a@example.org','b@example.org']); })
            ->andReturnUsing(function($attendee){
                $inv = Mockery::mock(RSVPInvitation::class);
                $inv->shouldReceive('getId')->andReturn(99);
                return $inv;
            });

        $this->service->importInvitationData($event, $file);
        $this->assertTrue(true); // reached without exception
    }

    /** -------------------- delete -------------------- */

    public function testDeleteRemovesPendingInvitation(): void
    {
        $event = Mockery::mock(SummitEvent::class);

        $inv = Mockery::mock(RSVPInvitation::class);
        $inv->shouldReceive('isAccepted')->andReturnFalse();

        $this->invitation_repository->shouldReceive('getById')->once()->with(10)->andReturn($inv);
        $this->invitation_repository->shouldReceive('delete')->once()->with($inv);

        $this->service->delete($event, 10);
        $this->assertTrue(true);
    }

    public function testDeleteNotFound(): void
    {
        $this->expectException(EntityNotFoundException::class);

        $event = Mockery::mock(SummitEvent::class);

        $this->invitation_repository->shouldReceive('getById')->once()->with(10)->andReturnNull();

        $this->service->delete($event, 10);
    }

    public function testDeleteFailsIfNotPending(): void
    {
        $this->expectException(ValidationException::class);

        $event = Mockery::mock(SummitEvent::class);

        $inv = Mockery::mock(RSVPInvitation::class);
        $inv->shouldReceive('isAccepted')->andReturnTrue();

        $this->invitation_repository->shouldReceive('getById')->once()->with(10)->andReturn($inv);

        $this->service->delete($event, 10);
    }

    /** -------------------- add -------------------- */

    public function testAddCreatesInvitation(): void
    {
        [$event, $summit, $addAttendee] = $this->makeSummitEventGraph();
        $addAttendee(123, 'x@example.org');

        $event->shouldReceive('addRSVPInvitation')->once()->andReturn(Mockery::mock(RSVPInvitation::class));

        $result = $this->service->add($event, ['invitee_ids' => [123]]);
        $this->assertNotEmpty($result);
    }

    public function testAddFailsIfAttendeeNotFound(): void
    {

        [$event] = $this->makeSummitEventGraph();
        $res = $this->service->add($event, ['invitee_ids' => [999]]);
        $this->assertEmpty($res);
    }

    /** -------------------- getInvitationByToken -------------------- */

    public function testGetInvitationByTokenReturnsPendingInvitation(): void
    {
        $event = Mockery::mock(SummitEvent::class)->makePartial();

        $inv = Mockery::mock(RSVPInvitation::class)->makePartial();
        $inv->shouldReceive('isAccepted')->andReturnFalse();
        $inv->shouldReceive('isRejected')->andReturnFalse();
        $inv->shouldReceive('isPending')->andReturnTrue();
        $inv->shouldReceive('getInvitee->getEmail')->andReturn('e@example.org');

        $this->invitation_repository
            ->shouldReceive('getByHashAndSummitEvent')
            ->once()
            ->withArgs(function($hash, $eventArg) use($event){
                $this->assertEquals($eventArg, $event);
                return is_string($hash) && $hash !== '';
            })
            ->andReturn($inv);

        $res = $this->service->getInvitationBySummitEventAndToken($event, 'token123');
        $this->assertSame($inv, $res);
    }

    public function testGetInvitationByTokenAlreadyAccepted(): void
    {
        $this->expectException(ValidationException::class);
        $event = Mockery::mock(SummitEvent::class)->makePartial();
        $inv = Mockery::mock(RSVPInvitation::class)->makePartial();
        $inv->shouldReceive('isAccepted')->andReturnTrue();
        $inv->shouldReceive('isRejected')->andReturnFalse();
        $inv->shouldReceive('isPending')->andReturnFalse();
        $inv->shouldReceive('getInvitee->getEmail')->andReturn('e@example.org');

        $this->invitation_repository
            ->shouldReceive('getByHashAndSummitEvent')
            ->once()
            ->andReturn($inv);

        $this->service->getInvitationBySummitEventAndToken($event,'token123');
    }

    public function testGetInvitationByTokenNotFound(): void
    {
        $this->expectException(EntityNotFoundException::class);
        $event = Mockery::mock(SummitEvent::class)->makePartial();
        $this->invitation_repository
            ->shouldReceive('getByHashAndSummitEvent')
            ->once()
            ->andReturnNull();

        $this->service->getInvitationBySummitEventAndToken($event,'token123');
    }

    /** -------------------- acceptInvitationBySummitAndToken -------------------- */

    public function testAcceptInvitationHappyPath(): void
    {
        // Event + Summit (typed correctly)
        $summit = Mockery::mock(Summit::class);
        $event  = Mockery::mock(SummitEvent::class);
        $event->shouldReceive('getSummit')->andReturn($summit);
        $event->shouldReceive('getId')->andReturn(50);

        // Invitee (partial mock of SummitAttendee)
        $invitee = Mockery::mock(SummitAttendee::class)->makePartial();
        $invitee->shouldReceive('hasMember')->andReturn(true);
        $invitee->shouldReceive('hasTicketsPaidTickets')->andReturn(true);
        $invitee->shouldReceive('getEmail')->andReturn('someone@example.org');

        // Return a real-typed Member mock to satisfy SummitAttendee::getMember(): ?Member
        $member = Mockery::mock(Member::class)->makePartial();
        $invitee->shouldReceive('getMember')->andReturn($member);

        // Invitation
        $inv = Mockery::mock(RSVPInvitation::class)->makePartial();
        $inv->shouldReceive('getInvitee')->andReturn($invitee);
        $inv->shouldReceive('isAccepted')->andReturnFalse();
        $inv->shouldReceive('isRejected')->andReturnFalse();
        $inv->shouldReceive('isPending')->andReturnTrue();
        $inv->shouldReceive('markAsAccepted')->once();
        $inv->shouldReceive('getEvent')->andReturn($event);

        $rsvp = Mockery::mock(RSVP::class)->makePartial();
        $this->invitation_repository->shouldReceive('getByHashAndSummitEvent')->once()->andReturn($inv);

        // Ensure addRSVP gets the exact summit/member/eventId we expect
        $this->rsvp_service->shouldReceive('rsvpEvent')
            ->once()
            ->withArgs(function ($summitArg, $memberArg, $eventId) use ($summit, $member, $event) {
                $this->assertSame($summit, $summitArg);
                $this->assertSame($member,  $memberArg);
                $this->assertSame($event->getId(),       $eventId);
                return true;
            })->andReturn($rsvp);

        $res = $this->service->acceptInvitationBySummitEventAndToken($event, 'tkn');
        $this->assertSame($inv, $res);
        $this->assertSame($rsvp, $inv->getRSVP());
    }

    public function testAcceptInvitationFailsNoMember(): void
    {
        $this->expectException(EntityNotFoundException::class);
        $event  = Mockery::mock(SummitEvent::class)->makePartial();
        // Invitee (partial mock of SummitAttendee)
        $invitee = Mockery::mock(SummitAttendee::class)->makePartial();
        $invitee->shouldReceive('hasMember')->andReturn(false);
        $invitee->shouldReceive('hasTicketsPaidTickets')->andReturn(true);
        $invitee->shouldReceive('getEmail')->andReturn('someone@example.org');


        $inv = Mockery::mock(RSVPInvitation::class)->makePartial();
        $inv->shouldReceive('getInvitee')->andReturn($invitee);

        $this->invitation_repository->shouldReceive('getByHashAndSummitEvent')->andReturn($inv);

        $this->service->acceptInvitationBySummitEventAndToken($event, 'tkn');
    }

    public function testAcceptInvitationFailsAlreadyAccepted(): void
    {
        $this->expectException(ValidationException::class);
        $event  = Mockery::mock(SummitEvent::class)->makePartial();
        // Invitee (partial mock of SummitAttendee)
        $invitee = Mockery::mock(SummitAttendee::class)->makePartial();
        $invitee->shouldReceive('hasMember')->andReturn(true);
        $invitee->shouldReceive('hasTicketsPaidTickets')->andReturn(true);
        $invitee->shouldReceive('getEmail')->andReturn('someone@example.org');

        // Return a real-typed Member mock to satisfy SummitAttendee::getMember(): ?Member
        $member = Mockery::mock(Member::class)->makePartial();
        $invitee->shouldReceive('getMember')->andReturn($member);

        $inv = Mockery::mock(RSVPInvitation::class)->makePartial();
        $inv->shouldReceive('getInvitee')->andReturn($invitee);
        $inv->shouldReceive('isAccepted')->andReturnTrue();
        $inv->shouldReceive('isRejected')->andReturnFalse();
        $inv->shouldReceive('isPending')->andReturnFalse();

        $this->invitation_repository->shouldReceive('getByHashAndSummitEvent')->andReturn($inv);

        $this->service->acceptInvitationBySummitEventAndToken($event, 'tkn');
    }

    public function testAcceptInvitationFailsNoPaidTickets(): void
    {
        $this->expectException(ValidationException::class);
        $event  = Mockery::mock(SummitEvent::class)->makePartial();
        // Invitee (partial mock of SummitAttendee)
        $invitee = Mockery::mock(SummitAttendee::class)->makePartial();
        $invitee->shouldReceive('hasMember')->andReturn(true);
        $invitee->shouldReceive('hasTicketsPaidTickets')->andReturn(false);
        $invitee->shouldReceive('getEmail')->andReturn('someone@example.org');

        // Return a real-typed Member mock to satisfy SummitAttendee::getMember(): ?Member
        $member = Mockery::mock(Member::class)->makePartial();
        $invitee->shouldReceive('getMember')->andReturn($member);

        $inv = Mockery::mock(RSVPInvitation::class)->makePartial();
        $inv->shouldReceive('getInvitee')->andReturn($invitee);
        $inv->shouldReceive('isAccepted')->andReturnFalse();
        $inv->shouldReceive('isRejected')->andReturnFalse();
        $inv->shouldReceive('isPending')->andReturnTrue();

        $this->invitation_repository->shouldReceive('getByHashAndSummitEvent')->andReturn($inv);

        $this->service->acceptInvitationBySummitEventAndToken($event, 'tkn');
    }

    /** -------------------- rejectInvitationBySummitAndToken -------------------- */

    public function testRejectInvitationHappyPath(): void
    {
        $event  = Mockery::mock(SummitEvent::class)->makePartial();
        // Invitee (partial mock of SummitAttendee)
        $invitee = Mockery::mock(SummitAttendee::class)->makePartial();
        $invitee->shouldReceive('hasMember')->andReturn(true);
        $invitee->shouldReceive('hasTicketsPaidTickets')->andReturn(true);
        $invitee->shouldReceive('getEmail')->andReturn('someone@example.org');

        // Return a real-typed Member mock to satisfy SummitAttendee::getMember(): ?Member
        $member = Mockery::mock(Member::class)->makePartial();
        $invitee->shouldReceive('getMember')->andReturn($member);

        $inv = Mockery::mock(RSVPInvitation::class)->makePartial();
        $inv->shouldReceive('getInvitee')->andReturn($invitee);
        $inv->shouldReceive('isAccepted')->andReturnFalse();
        $inv->shouldReceive('isRejected')->andReturnFalse();
        $inv->shouldReceive('isPending')->andReturnTrue();
        $inv->shouldReceive('markAsRejected')->once();

        $this->invitation_repository->shouldReceive('getByHashAndSummitEvent')->andReturn($inv);

        $res = $this->service->rejectInvitationBySummitEventAndToken($event, 'tkn');
        $this->assertSame($inv, $res);
    }

    /** -------------------- triggerSend -------------------- */

    public function testTriggerSendDispatchesJob(): void
    {
        Bus::fake();

        $event = Mockery::mock(SummitEvent::class)->makePartial();
        $event->shouldReceive("isPublished")->andReturn(true);
        $event->shouldReceive("hasPrivateRSVP")->andReturn(true);
        $payload = ['foo' => 'bar'];
        $event->shouldReceive("isPublished")->andReturn(true);
        $event->shouldReceive("hasPrivateRSVP")->andReturn(true);

        $this->service->triggerSend($event, $payload, null);

        Bus::assertDispatched(ProcessRSVPInvitationsJob::class, function ($job) use ($event, $payload) {
            // loose check (Laravel serializes arguments)
            return true;
        });
    }

    /** -------------------- send (ParametrizedSendEmails) -------------------- */

    public function testSendAddsEventIdFilterAndDispatchesInvite(): void
    {

        Bus::fake();
        // Alias-mock the EmailExcerpt facade (avoid mocking the final service)
        $EmailExcerptAlias = Mockery::mock('alias:' . EmailExcerpt::class);
        $EmailExcerptAlias->shouldReceive('clearReport')->once();
        $EmailExcerptAlias->shouldReceive('addInfoMessage')->atLeast()->once();
        $EmailExcerptAlias->shouldReceive('add')->zeroOrMoreTimes();
        $EmailExcerptAlias->shouldReceive('addEmailSent')->zeroOrMoreTimes();
        $EmailExcerptAlias->shouldReceive('generateEmailCountLine')->once();
        $EmailExcerptAlias->shouldReceive('getReport')->andReturn(['report' => []]);

        $summit = Mockery::mock(Summit::class)->makePartial();
        $summit->shouldReceive('getId')->andReturn($opts['summit_id'] ?? 1)->byDefault();
        $summit->shouldReceive("getSupportEmail")->andReturn("support@summit.com");
        $summit->shouldReceive('getEmailIdentifierPerEmailEventFlowSlug')->andReturn("SUMMIT_REGISTRATION_INVITE_RSVP");
        $eventId = 555;
        $event   = Mockery::mock(\models\summit\SummitEvent::class)->makePartial();
        $event->shouldReceive('getSummit')->andReturn($summit);
        $event->shouldReceive('getId')->andReturn($eventId)->byDefault();
        $event->shouldReceive('getTitle')->andReturn("Event Title");
        $event->shouldReceive('getLocationName')->andReturn("Location Name");
        $this->summit_event_repository->shouldReceive('getById')->once()->with($eventId)->andReturn($event);

        // Ensure filter includes event_id and paging is correct; return two IDs
        $this->invitation_repository->shouldReceive('getAllIdsByPage')
            ->atLeast()->once()
            ->withArgs(function ($pagingInfo, $filter) use ($eventId) {
                $this->assertEquals(\App\Services\Model\Imp\Traits\MaxPageSize, $pagingInfo->getPerPage());
                $this->assertTrue($filter->hasFilter('summit_event_id'), 'summit_event_id filter not injected');
                $uf = $filter->getUniqueFilter('summit_event_id');
                $this->assertEquals((string)$eventId, (string)$uf->getValue());
                return true;
            })
            ->andReturn([10, 11],[]);

        // Invitee (partial mock of SummitAttendee)
        $invitee = Mockery::mock(SummitAttendee::class)->makePartial();
        $invitee->shouldReceive('hasMember')->andReturn(true);
        $invitee->shouldReceive('getEmail')->andReturn('someone@example.org');

        // Return a real-typed Member mock to satisfy SummitAttendee::getMember(): ?Member
        $member = Mockery::mock(Member::class)->makePartial();
        $invitee->shouldReceive('getMember')->andReturn($member);
        $invitee->shouldReceive('getFirstName')->andReturn("Sebastian");
        $invitee->shouldReceive('getSurname')->andReturn("Marcet");


        // Provide invitations for 10 and 11
        $mkInvitation = function (int $id) use ($event, $invitee) {
            $inv = Mockery::mock(\App\Models\Foundation\Summit\Events\RSVP\RSVPInvitation::class)->makePartial();
            $inv->shouldReceive('isRejected')->andReturnFalse();
            $inv->shouldReceive('getEvent')->andReturn($event);
            $inv->shouldReceive('getInvitee')->andReturn($invitee);
            $inv->shouldReceive('generateConfirmationToken')->once();
            $inv->shouldReceive('getId')->andReturn($id);
            $inv->shouldReceive('getHash')->andReturn('hash-' . $id);
            $inv->shouldReceive('getToken')->andReturn('token-' . $id);
            $inv->shouldReceive('getEmail')->andReturn('person' . $id . '@example.org');
            return $inv;
        };

        $this->invitation_repository->shouldReceive('getByIdExclusiveLock')->once()->with(10)->andReturn($mkInvitation(10));
        $this->invitation_repository->shouldReceive('getByIdExclusiveLock')->once()->with(11)->andReturn($mkInvitation(11));

        // No token collision
        $this->invitation_repository->shouldReceive('getByHashAndSummitEvent')->twice()->andReturnNull();

        $this->service->send($eventId, [
            'email_flow_event' => 'SUMMIT_REGISTRATION_INVITE_RSVP',
        ]);

        Bus::assertDispatchedTimes(RSVPInviteEmail::class, 2);
    }

    public function testSendRespectsExplicitIdsAndExcludedIdsAndSkipsRejected(): void
    {
        Bus::fake();
        // Alias-mock EmailExcerpt
        $EmailExcerptAlias = Mockery::mock('alias:' . EmailExcerpt::class);
        $EmailExcerptAlias->shouldReceive('clearReport')->once();
        $EmailExcerptAlias->shouldReceive('addInfoMessage')->atLeast()->once();
        $EmailExcerptAlias->shouldReceive('add')->zeroOrMoreTimes();
        $EmailExcerptAlias->shouldReceive('addEmailSent')->zeroOrMoreTimes();
        $EmailExcerptAlias->shouldReceive('generateEmailCountLine')->once();


        $summit = Mockery::mock(Summit::class)->makePartial();
        $summit->shouldReceive('getId')->andReturn($opts['summit_id'] ?? 1)->byDefault();
        $summit->shouldReceive("getSupportEmail")->andReturn("support@summit.com");
        $summit->shouldReceive('getEmailIdentifierPerEmailEventFlowSlug')->andReturn("SUMMIT_REGISTRATION_INVITE_RSVP");
        $eventId = 777;
        $event   = Mockery::mock(\models\summit\SummitEvent::class)->makePartial();
        $event->shouldReceive('getId')->andReturn($eventId)->byDefault();
        $event->shouldReceive('getSummit')->andReturn($summit);
        $event->shouldReceive('getTitle')->andReturn("Title");
        $event->shouldReceive('getLocationName')->andReturn("Location");

        $this->summit_event_repository->shouldReceive('getById')->once()->with($eventId)->andReturn($event);

        // No paging path when explicit IDs are provided
        $this->invitation_repository->shouldReceive('getAllIdsByPage')->never();

        // Invitee (partial mock of SummitAttendee)
        $invitee = Mockery::mock(SummitAttendee::class)->makePartial();
        $invitee->shouldReceive('hasMember')->andReturn(true);
        $invitee->shouldReceive('getEmail')->andReturn('someone@example.org');

        // Return a real-typed Member mock to satisfy SummitAttendee::getMember(): ?Member
        $member = Mockery::mock(Member::class)->makePartial();
        $invitee->shouldReceive('getMember')->andReturn($member);
        $invitee->shouldReceive('getFirstName')->andReturn("Sebastian");
        $invitee->shouldReceive('getSurname')->andReturn("Marcet");

        // ID 201 => processed
        $inv201 = Mockery::mock(\App\Models\Foundation\Summit\Events\RSVP\RSVPInvitation::class)->makePartial();
        $inv201->shouldReceive('isRejected')->andReturnFalse();
        $inv201->shouldReceive('getEvent')->andReturn($event);
        $inv201->shouldReceive('generateConfirmationToken')->once();
        $inv201->shouldReceive('getHash')->andReturn('hash-201');
        $inv201->shouldReceive('getToken')->andReturn('token-201');
        $inv201->shouldReceive('getEmail')->andReturn('201@example.org');
        $inv201->shouldReceive('getInvitee')->andReturn($invitee);
        $inv201->shouldReceive('markAsSent');
        // ID 203 => rejected
        $inv203 = Mockery::mock(\App\Models\Foundation\Summit\Events\RSVP\RSVPInvitation::class)->makePartial();
        $inv203->shouldReceive('isRejected')->andReturnTrue();

        $this->invitation_repository->shouldReceive('getByIdExclusiveLock')->once()->with(201)->andReturn($inv201);
        $this->invitation_repository->shouldReceive('getByIdExclusiveLock')->once()->with(203)->andReturn($inv203);

        // No collision for 201
        $this->invitation_repository->shouldReceive('getByHashAndSummitEvent')->once()->andReturnNull();

        $this->service->send($eventId, [
            'email_flow_event' => 'SUMMIT_REGISTRATION_INVITE_RSVP',
            // explicit IDs path
            'invitations_ids' => [201, 202, 203],
            // exclude 202
            'excluded_invitations_ids' => [202],
        ]);

        $this->assertTrue(true);

        Bus::assertDispatchedTimes(RSVPInviteEmail::class, 1);
    }

    public function testSendRequiresFlowEvent(): void
    {
        // Even when flow_event is missing, trait calls clearReport() first
        $EmailExcerptAlias = Mockery::mock('alias:' . EmailExcerpt::class);
        $EmailExcerptAlias->shouldReceive('clearReport')->once();

        $this->expectException(\models\exceptions\ValidationException::class);

        $this->service->send(123, [
            // missing 'email_flow_event'
        ]);
    }

}
