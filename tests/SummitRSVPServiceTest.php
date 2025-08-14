<?php

use App\Events\RSVP\RSVPDeleted;
use App\Events\RSVP\RSVPUpdated;
use App\Services\Model\Imp\SummitRSVPService;
use Illuminate\Support\Facades\Log;
use libs\utils\ITransactionService;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use Laravel\BrowserKitTesting\TestCase;
use models\summit\Summit;
use models\summit\SummitEvent;
use models\summit\RSVP;
use models\main\Member;
use Illuminate\Support\Facades\Event;
use App\Events\RSVP\RSVPCreated;
/**
 * @covers \App\Services\Model\Imp\SummitRSVPService
 */
class SummitRSVPServiceTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /** @var Mockery\MockInterface */
    private $event_repository;

    /** @var Mockery\MockInterface */
    private $rsvp_repository;

    /** @var SummitRSVPService */
    private $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->event_repository = Mockery::mock('models\summit\ISummitEventRepository');
        $this->rsvp_repository  = Mockery::mock('models\summit\IRSVPRepository');

        // simple inline transaction service
        $tx_service         = new class implements ITransactionService {
            public function transaction(Closure $callback, int $isolationLevel = 2)
            {
                // run inline for tests
                return $callback();
            }
        };

        $this->service = new \App\Services\Model\Imp\SummitRSVPService(
            $this->event_repository,
            $this->rsvp_repository,
            $tx_service
        );

        // silence logs
        Log::swap(Mockery::mock(\Psr\Log\LoggerInterface::class)->shouldIgnoreMissing());
    }

    private function mockSummit(int $id = 1)
    {
        $summit = Mockery::mock(Summit::class)->makePartial();
        $summit->shouldReceive('getId')->andReturn($id);
        return $summit;
    }

    private function mockMember(int $id = 10)
    {
        $member = Mockery::mock(Member::class)->makePartial();
        $member->shouldReceive('getId')->andReturn($id)->byDefault();
        return $member;
    }

    private function mockEvent(int $eventId = 100, int $summitId = 1)
    {
        $event = Mockery::mock(SummitEvent::class)->makePartial();
        $event->shouldReceive('getId')->andReturn($eventId)->byDefault();
        $event->shouldReceive('getSummitId')->andReturn($summitId)->byDefault();
        return $event;
    }

    /** *********************************************************************
     * addRSVP
     ********************************************************************* */

    public function testAddRSVPHappyPath(): void
    {

        Event::fake();
        $summit_attendee = Mockery::mock(\models\summit\SummitAttendee::class)->makePartial();
        $summit_attendee->shouldReceive('hasTicketsPaidTickets')->andReturn(true);
        $summit = $this->mockSummit(1);
        $summit->shouldReceive("getAttendeeByMember")->andReturn($summit_attendee);
        $member = $this->mockMember(10);

        $event  = $this->mockEvent(100, 1);
        $event->shouldReceive('getSummit')->andReturn($summit);
        $event->shouldReceive('getId')->andReturn(1);
        $event->shouldReceive('hasRSVP')->andReturn(true);
        $event->shouldReceive('getRSVPSeatTypeCount')->with(RSVP::SeatTypeRegular)->andReturn(10);
        $event->shouldReceive('getRSVPMaxUserNumber')->andReturn(11);
        $event->shouldReceive("getRSVPType")->andReturn(SummitEvent::RSVPType_Private);
        $event->shouldReceive("hasInvitationFor")->andReturn(true);
        // init the collection used by addRSVPSubmission()
        $prop = new \ReflectionProperty(\models\summit\SummitEvent::class, 'rsvp');
        $prop->setAccessible(true);
        $prop->setValue($event, new \Doctrine\Common\Collections\ArrayCollection());

        // also avoid the template check if not relevant
        $event->shouldReceive('hasRSVPTemplate')->andReturn(false);

        $event_type = Mockery::mock(\models\summit\SummitEventType::class)->makePartial();
        $event_type->shouldReceive('isPrivate')->andReturn(false);
        $event->shouldReceive('getType')->andReturn($event_type);

        $this->event_repository->shouldReceive('getByIdExclusiveLock')->once()->with(1)->andReturn($event);
        $member->shouldReceive('getRsvpByEvent')->once()->with(1)->andReturnNull();

        // Factory returns OUR $rsvp so we can match identity in the event
        $rsvp = Mockery::mock(RSVP::class)->makePartial();
        Mockery::mock('alias:App\Models\Foundation\Summit\Factories\SummitRSVPFactory')
            ->shouldReceive('build')
            ->once()
            ->with($event, $member, Mockery::type('array'))
            ->andReturn($rsvp);
        $rsvp->shouldReceive("getId")->andReturn(1);
        $rsvp->shouldReceive("getOwnerId")->andReturn(1);
        $rsvp->shouldReceive("getEventId")->andReturn(1);

        $res = $this->service->addRSVP($summit, $member, 1);
        $this->assertTrue($res instanceof RSVP);

        Event::assertDispatched(RSVPCreated::class, function ($event) use ($rsvp) {
            return $event->getRsvpId() === $rsvp->getId();
        });
    }

    public function testAddRSVPEventNotFound(): void
    {
        $this->expectException(EntityNotFoundException::class);

        $summit = $this->mockSummit(1);
        $member = $this->mockMember(10);

        $this->event_repository->shouldReceive('getByIdExclusiveLock')->once()->with(100)->andReturnNull();

        $this->service->addRSVP($summit, $member, 100, []);
    }

    public function testAddRSVPSummitMismatch(): void
    {
        $this->expectException(EntityNotFoundException::class);


        $summit = $this->mockSummit(1);
        $member = $this->mockMember(10);
        $event  = $this->mockEvent(100, 2); // mismatch

        $this->event_repository->shouldReceive('getByIdExclusiveLock')->once()->with(100)->andReturn($event);

        $this->service->addRSVP($summit, $member, 100, []);
    }

    public function testAddRSVPNoRSVPOnEvent(): void
    {
        $this->expectException(EntityNotFoundException::class);

        $summit = $this->mockSummit(1);
        $member = $this->mockMember(10);
        $event  = $this->mockEvent(100, 1);

        $event_type = Mockery::mock(\models\summit\SummitEventType::class)->makePartial();
        $event_type->shouldReceive('isPrivate')->andReturn(false);
        $event->shouldReceive('getType')->andReturn($event_type);

        $event->shouldReceive('hasRSVP')->andReturn(false);

        $this->event_repository->shouldReceive('getByIdExclusiveLock')->once()->with(100)->andReturn($event);

        $this->service->addRSVP($summit, $member, 100, []);
    }

    public function testAddRSVPAlreadyExists(): void
    {
        $this->expectException(ValidationException::class);
        $summit_attendee = Mockery::mock(\models\summit\SummitAttendee::class)->makePartial();
        $summit_attendee->shouldReceive('hasTicketsPaidTickets')->andReturn(true);
        $summit = $this->mockSummit(1);
        $summit->shouldReceive("getAttendeeByMember")->andReturn($summit_attendee);
        $member = $this->mockMember(10);
        $event  = $this->mockEvent(100, 1);

        $event_type = Mockery::mock(\models\summit\SummitEventType::class)->makePartial();
        $event_type->shouldReceive('isPrivate')->andReturn(false);
        $event->shouldReceive('getType')->andReturn($event_type);
        $event->shouldReceive('hasRSVP')->andReturn(true);
        $event->shouldReceive("getRSVPType")->andReturn(SummitEvent::RSVPType_Public);
        $this->event_repository->shouldReceive('getByIdExclusiveLock')->once()->with(100)->andReturn($event);

        $existing = Mockery::mock(RSVP::class)->makePartial();
        $member->shouldReceive('getRsvpByEvent')->once()->with(100)->andReturn($existing);

        $this->service->addRSVP($summit, $member, 100, []);
    }

    /** *********************************************************************
     * unRSVPEvent
     ********************************************************************* */

    public function testUnRSVPEventHappyPathRegularWithWaitlistUpgrade(): void
    {
        Event::fake();

        $summit = $this->mockSummit(1);
        $member = $this->mockMember(10);
        $event  = $this->mockEvent(100, 1);
        $event_type = Mockery::mock(\models\summit\SummitEventType::class)->makePartial();
        $event_type->shouldReceive('isPrivate')->andReturn(false);
        $event->shouldReceive('getType')->andReturn($event_type);

        $this->event_repository->shouldReceive('getByIdExclusiveLock')->once()->with(100)->andReturn($event);

        $rsvp = Mockery::mock(RSVP::class)->makePartial();
        $rsvp->shouldReceive('getSeatType')->andReturn(\models\summit\RSVP::SeatTypeRegular);
        $rsvp->shouldReceive("getOwnerId")->once()->andReturn(1);
        $rsvp->shouldReceive("getEventId")->once()->andReturn(1);
        $member->shouldReceive('getRsvpByEvent')->once()->with(100)->andReturn($rsvp);

        $wait = Mockery::mock(RSVP::class)->makePartial();
        $wait->shouldReceive('getId')->andReturn(999);
        $wait->shouldReceive('upgradeToRegularSeatType')->once();
        $wait->shouldReceive("getOwnerId")->once()->andReturn(1);
        $wait->shouldReceive("getEventId")->once()->andReturn(1);
        $event->shouldReceive('getFirstRSVPOnWaitList')->once()->andReturn($wait);

        $this->rsvp_repository->shouldReceive('delete')->once()->with($rsvp);

        $this->service->unRSVPEvent($summit, $member, 100);

        Event::assertDispatched(RSVPUpdated::class, function ($event) use ($rsvp) { return true;});
        Event::assertDispatched(RSVPDeleted::class, function ($event) use ($rsvp) { return true;});
    }

    public function testUnRSVPEventHappyPathRegularNoWaitlist(): void
    {

        Event::fake();

        $summit = $this->mockSummit(1);
        $member = $this->mockMember(10);
        $event  = $this->mockEvent(100, 1);
        $event_type = Mockery::mock(\models\summit\SummitEventType::class)->makePartial();
        $event_type->shouldReceive('isPrivate')->andReturn(false);
        $event->shouldReceive('getType')->andReturn($event_type);

        $this->event_repository->shouldReceive('getByIdExclusiveLock')->once()->with(100)->andReturn($event);

        $rsvp = Mockery::mock(RSVP::class)->makePartial();
        $rsvp->shouldReceive("getOwnerId")->once()->andReturn(1);
        $rsvp->shouldReceive("getEventId")->once()->andReturn(1);
        $rsvp->shouldReceive('getSeatType')->andReturn(RSVP::SeatTypeWaitList); // not regular? -> no waitlist upgrade path

        $member->shouldReceive('getRsvpByEvent')->once()->with(100)->andReturn($rsvp);

        $event->shouldReceive('getFirstRSVPOnWaitList')->never();
        $this->rsvp_repository->shouldReceive('delete')->once()->with($rsvp);

        $this->service->unRSVPEvent($summit, $member, 100);
        $this->assertTrue(true);

        Event::assertDispatched(RSVPDeleted::class, function ($event) use ($rsvp) { return true;});
    }

    public function testUnRSVPEventNoExistingRSVP(): void
    {
        $this->expectException(ValidationException::class);

        $summit = $this->mockSummit(1);
        $member = $this->mockMember(10);
        $event  = $this->mockEvent(100, 1);
        $event_type = Mockery::mock(\models\summit\SummitEventType::class)->makePartial();
        $event_type->shouldReceive('isPrivate')->andReturn(false);
        $event->shouldReceive('getType')->andReturn($event_type);

        $this->event_repository->shouldReceive('getByIdExclusiveLock')->once()->with(100)->andReturn($event);

        $member->shouldReceive('getRsvpByEvent')->once()->with(100)->andReturnNull();
        $this->rsvp_repository->shouldReceive('delete')->never();

        $this->service->unRSVPEvent($summit, $member, 100);
    }

    public function testUnRSVPEventEventNotFound(): void
    {
        $this->expectException(EntityNotFoundException::class);

        $summit = $this->mockSummit(1);
        $member = $this->mockMember(10);

        $this->event_repository->shouldReceive('getByIdExclusiveLock')->once()->with(100)->andReturnNull();

        $this->service->unRSVPEvent($summit, $member, 100);
    }

}
