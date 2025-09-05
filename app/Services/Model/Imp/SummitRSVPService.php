<?php namespace App\Services\Model\Imp;
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

use App\Events\RSVP\RSVPCreated;
use App\Events\RSVP\RSVPDeleted;
use App\Events\RSVP\RSVPUpdated;
use App\Events\ScheduleEntityLifeCycleEvent;
use App\Models\Foundation\Summit\Events\RSVP\RSVPInvitation;
use App\Models\Foundation\Summit\Factories\AdminSummitRSVPFactory;
use App\Models\Foundation\Summit\Factories\SummitRSVPFactory;
use App\Services\Model\AbstractService;
use App\Services\Model\ISummitRSVPService;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use libs\utils\ITransactionService;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use models\main\Member;
use models\summit\IRSVPRepository;
use models\summit\ISummitEventRepository;
use models\summit\Presentation;
use models\summit\RSVP;
use models\summit\Summit;
use models\summit\SummitAttendee;
use models\summit\SummitEvent;

class SummitRSVPService extends AbstractService
    implements ISummitRSVPService
{

    /**
     * @var ISummitEventRepository
     */
    private ISummitEventRepository $event_repository;

    private IRSVPRepository $rsvp_repository;


    /**
     * @param ISummitEventRepository $event_repository
     * @param IRSVPRepository $rsvp_repository
     * @param ITransactionService $transaction_service
     */
    public function __construct(
        ISummitEventRepository $event_repository,
        IRSVPRepository        $rsvp_repository,
        ITransactionService    $transaction_service,
    )
    {
        parent::__construct($transaction_service);
        $this->event_repository = $event_repository;
        $this->rsvp_repository = $rsvp_repository;
    }

    /**
     * @param Summit $summit
     * @param Member $member
     * @param int $event_id
     * @param array $payload
     * @return RSVP
     * @throws \Exception
     */
    public function rsvpEvent(Summit $summit, Member $member, int $event_id, array $payload = []): RSVP
    {
        $rsvp = $this->tx_service->transaction(function () use ($summit, $member, $event_id, $payload) {

            Log::debug
            (
                sprintf
                (
                    "SummitRSVPService::rsvpEvent summit %s member %s event_id payload %s",
                    $summit->getId(),
                    $member->getId(),
                    $event_id,
                    json_encode($payload)
                )
            );

            $event = $this->assertEventVisibleToMember
            (
                $this->assertEventHasRSVP
                (
                    $this->loadEventOrFail
                    (
                        $summit, $event_id
                    )
                ),
                $member
            );

            $attendee = $this->assertAttendeeHasPaidTickets($this->getAttendeeForMemberOrFail($summit, $member));

            $invitation = $this->checkIfAttendeeNeedIRSVPInvitation($event, $attendee);

            $this->assertDoesNotHaveFormerRSVP($member, $event_id);

            // create RSVP

            $rsvp = SummitRSVPFactory::build($event, $member, $payload);
            $rsvp->setActionSource(RSVP::ActionSource_Schedule);
            $rsvp->activate();
            if (!is_null($invitation)) $invitation->markAsAcceptedWithRSVP($rsvp);
            return $rsvp;
        });

        Event::dispatch(new RSVPCreated($rsvp));
        $this->emitSummitEventDomainEvent($rsvp->getEvent());
        return $rsvp;
    }


    /**
     * @param SummitEvent $event
     * @param int $rsvp_id
     * @param array $payload
     * @return RSVP
     * @throws \Exception
     */
    public function update(SummitEvent $event, int $rsvp_id, array $payload): RSVP
    {
        return $this->tx_service->transaction(function () use ($event, $rsvp_id, $payload) {

            $rsvp = $event->getRSVPById($rsvp_id);
            if (is_null($rsvp))
                throw new EntityNotFoundException("RSVP not found.");

            // update RSVP

            $rsvp = AdminSummitRSVPFactory::populate($rsvp, $event, null, $payload);

            Event::dispatch(new RSVPUpdated($rsvp));

            return $rsvp;
        });
    }

    /**
     * @param Summit $summit
     * @param Member $member
     * @param int $event_id
     * @return void
     * @throws \Exception
     */
    public function unRSVPEvent(Summit $summit, Member $member, int $event_id): void
    {
        $event = $this->tx_service->transaction(function () use ($summit, $member, $event_id) {

            Log::debug
            (
                sprintf
                (
                    "SummitRSVPService::unRSVPEvent summit %s member %s event_id payload %s",
                    $summit->getId(),
                    $member->getId(),
                    $event_id
                )
            );

            $event = $this->assertEventVisibleToMember
            (
                $this->loadEventOrFail
                (
                    $summit, $event_id
                ),
                $member
            );

            $rsvp = $member->getRsvpByEvent($event_id);

            if (is_null($rsvp))
                throw new ValidationException(sprintf("RSVP for event id %s does not exist for your member.", $event_id));

            $current_seat_type = $rsvp->getSeatType();

            // if removing a regular seat, try to promote first waitlisted
            if ($current_seat_type === RSVP::SeatTypeRegular) {
                Log::debug(sprintf("SummitRSVPService::unRSVPEvent rsvp %s is of type REGULAR", $rsvp->getId()));
                // we need to get the first on WAIT list and move it to REGULAR LIST
                // get the first one on SeatTypeWaitList
                $candidate = $event->getFirstRSVPOnWaitList();
                if (!is_null($candidate)) {
                    Log::debug
                    (
                        sprintf
                        (
                            "SummitRSVPService::unRSVPEvent got RSVP %s at WAIT LIST moving it to REGULAR ...",
                            $candidate->getId()
                        )
                    );
                    $candidate->upgradeToRegularSeatType();

                    Event::dispatch(new RSVPUpdated($candidate));
                }
            }

            $this->rsvp_repository->delete($rsvp);

            Event::dispatch(new RSVPDeleted($rsvp));
            return $event;
        });

        $this->emitSummitEventDomainEvent($event);
    }

    /**
     * @param Summit $summit
     * @param int $event_id
     * @param array $payload
     * @return RSVP
     * @throws \Exception
     */
    public function createRSVPFromPayload(Summit $summit, int $event_id, array $payload): RSVP
    {
        $rsvp = $this->tx_service->transaction(function () use ($summit, $event_id, $payload) {

            Log::debug
            (
                sprintf
                (
                    "SummitRSVPService::createFromPayload summit %s  event_id payload %s",
                    $summit->getId(),
                    $event_id,
                    json_encode($payload)
                )
            );

            $event =
                $this->assertEventHasRSVP
                (
                    $this->loadEventOrFail
                    (
                        $summit, $event_id
                    )
                );

            $attendee = $this->assertAttendeeHasPaidTickets($this->getAttendeeFromPayloadOrFail($summit, $payload));

            $member = $attendee->getMember();

            if (is_null($member)) {
                throw new EntityNotFoundException('Member not found.');
            }

            $this->assertDoesNotHaveFormerRSVP($member, $event_id);

            // create RSVP

            $seat_type = $payload['seat_type'] ?? null;
            if (is_null($seat_type)) {
                throw new ValidationException('Seat type is required.');
            }
            $current_seat_type = $event->getCurrentRSVPSubmissionSeatType();
            // If admin wants REGULAR but event is in WAITLIST mode, expand capacity to keep invariants
            if ($seat_type == RSVP::SeatTypeRegular && $current_seat_type == RSVP::SeatTypeWaitList) {
                Log::debug(sprintf("SummitRSVPService::createFromPayload current seat type %s .. need to increase chairs", $current_seat_type));
                // we need to increase the size to dont break the model
                $event->increaseRSVPMaxUserNumber();
            }
            $rsvp = AdminSummitRSVPFactory::build($event, $member, $payload);
            $rsvp->activate();
            return $rsvp;
        });

        Event::dispatch(new RSVPCreated($rsvp));
        $this->emitSummitEventDomainEvent($rsvp->getEvent());
        return $rsvp;
    }

    /**
     * @param Summit $summit
     * @param int $event_id
     * @return SummitEvent
     * @throws EntityNotFoundException
     */
    private function loadEventOrFail(Summit $summit, int $event_id): SummitEvent
    {
        Log::debug(sprintf(
            'SummitRSVPService::loadEventOrFail summit %d event_id %d',
            $summit->getId(),
            $event_id
        ));

        $event = $this->event_repository->getByIdExclusiveLock($event_id);
        if (!$event instanceof SummitEvent) {
            throw new EntityNotFoundException('Event not found on summit.');
        }
        if ($event->getSummitId() !== $summit->getId()) {
            throw new EntityNotFoundException('Event not found on summit.');
        }
        return $event;
    }

    /**
     * @param Member $member
     * @param int $event_id
     * @return void
     * @throws ValidationException
     */
    private function assertDoesNotHaveFormerRSVP(Member $member, int $event_id): void
    {
        $old_rsvp = $member->getRsvpByEvent($event_id);

        if (!is_null($old_rsvp))
            throw new ValidationException
            (
                sprintf
                (
                    "%s has already RSVPd to this event %s.",
                    $member->getEmail(),
                    $old_rsvp->getEvent()->getTitle(),
                )
            );
    }

    private function assertEventVisibleToMember(SummitEvent $event, Member $member): SummitEvent
    {
        if (!Summit::allowToSee($event, $member)) {
            throw new EntityNotFoundException('Event not found on summit.');
        }
        return $event;
    }

    private function assertEventHasRSVP(SummitEvent $event): SummitEvent
    {
        if (!$event->hasRSVP()) {
            throw new EntityNotFoundException('Event not found on summit.');
        }
        return $event;
    }

    private function assertEventHasRSVPTemplate(SummitEvent $event): void
    {
        if (!$event->hasRSVPTemplate()) {
            throw new EntityNotFoundException('Event not found on summit.');
        }
    }

    private function getAttendeeForMemberOrFail(Summit $summit, Member $member): SummitAttendee
    {
        $attendee = $summit->getAttendeeByMember($member);
        if (!$attendee instanceof SummitAttendee) {
            throw new ValidationException('Member has not a valid attendee at Summit.');
        }
        return $attendee;
    }

    private function assertAttendeeHasPaidTickets($attendee): SummitAttendee
    {
        if (!$attendee->hasTicketsPaidTickets()) {
            throw new ValidationException('Attendee has not any paid ticket at Summit.');
        }
        return $attendee;
    }

    /**
     * @param SummitEvent $event
     * @param SummitAttendee $attendee
     * @return RSVPInvitation|null
     * @throws ValidationException
     */
    private function checkIfAttendeeNeedIRSVPInvitation(SummitEvent $event, SummitAttendee $attendee): ?RSVPInvitation
    {
        if ($event->getRSVPType() !== SummitEvent::RSVPType_Private) {
            return null;
        }

        Log::debug(sprintf(
            'SummitRSVPService::checkIfAttendeeNeedIRSVPInvitation event %d RSVPType=Private',
            $event->getId()
        ));

        if (!$event->hasInvitationFor($attendee)) {
            Log::debug(sprintf(
                'SummitRSVPService::checkIfAttendeeNeedIRSVPInvitation attendee %s (%d) has no invitation for event %d',
                $attendee->getEmail(),
                $attendee->getId(),
                $event->getId()
            ));
            throw new ValidationException('Attendee does not have invitation for this Private RSVP activity.');
        }
        $invitation =  $event->getRSVPInvitationByInvitee($attendee);
        if(!$invitation->isPending()) {
            Log::debug(sprintf("SummitRSVPService::checkIfAttendeeNeedIRSVPInvitation invitation is not pending ( %s )", $invitation->getStatus()));
            throw new ValidationException('Attendee does not have invitation for this Private RSVP.');
        }
        return $invitation;
    }

    /**
     * @param Summit $summit
     * @param array $payload
     * @return SummitAttendee
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    private function getAttendeeFromPayloadOrFail(Summit $summit, array $payload): SummitAttendee
    {
        $attendee_id = $payload['attendee_id'] ?? null;
        if (is_null($attendee_id)) {
            throw new ValidationException('Attendee ID is required.');
        }

        $attendee = $summit->getAttendeeById($attendee_id);

        if (is_null($attendee)) {
            throw new EntityNotFoundException('Attendee not found.');
        }
        return $attendee;
    }

    /**
     * @param SummitEvent $event
     * @return void
     */
    private function emitSummitEventDomainEvent(SummitEvent $event): void
    {
        Event::dispatch(new ScheduleEntityLifeCycleEvent(ScheduleEntityLifeCycleEvent::Operation_Update,
            $event->getSummitId(),
            $event->getId(),
            Presentation::ClassName,
        ));
    }

}