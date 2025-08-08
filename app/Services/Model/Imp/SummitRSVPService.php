<?php namespace App\Services\Model\Imp;


use App\Events\RSVP\RSVPCreated;
use App\Events\RSVP\RSVPDeleted;
use App\Events\RSVP\RSVPUpdated;
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
use models\summit\RSVP;
use models\summit\Summit;
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
    public function addRSVP(Summit $summit, Member $member, int $event_id, array $payload = []): RSVP
    {
        $rsvp = $this->tx_service->transaction(function () use ($summit, $member, $event_id, $payload) {

            Log::debug
            (
                sprintf
                (
                    "SummitRSVPService::addRSVP summit %s member %s event_id payload %s",
                    $summit->getId(),
                    $member->getId(),
                    $event_id,
                    json_encode($payload)
                )
            );

            $event = $this->event_repository->getByIdExclusiveLock($event_id);

            if (!$event instanceof SummitEvent) {
                throw new EntityNotFoundException('Event not found on summit.');
            }

            if ($event->getSummitId() != $summit->getId()) {
                throw new EntityNotFoundException('Event not found on summit.');
            }

            if (!Summit::allowToSee($event, $member))
                throw new EntityNotFoundException('Event not found on summit.');

            if (!$event->hasRSVP()) {
                throw new EntityNotFoundException('Event not found on summit.');
            }


            $old_rsvp = $member->getRsvpByEvent($event_id);

            if (!is_null($old_rsvp))
                throw new ValidationException
                (
                    sprintf
                    (
                        "Member %s already submitted an rsvp for event %s on summit %s.",
                        $member->getId(),
                        $event_id,
                        $summit->getId()
                    )
                );

            // create RSVP

            return SummitRSVPFactory::build($event, $member, $payload);

        });

        Event::dispatch(new RSVPCreated($rsvp));

        return $rsvp;
    }

    /**
     * @param Summit $summit
     * @param Member $member
     * @param int $event_id
     * @param array $data
     * @return RSVP
     * @throws \Exception
     */
    public function updateRSVP(Summit $summit, Member $member, int $event_id, array $data): RSVP
    {
        return $this->tx_service->transaction(function () use ($summit, $member, $event_id, $data) {

            $event = $this->event_repository->getByIdExclusiveLock($event_id);

            if (!$event instanceof SummitEvent) {
                throw new EntityNotFoundException('Event not found on summit.');
            }

            if ($event->getSummitId() != $summit->getId()) {
                throw new EntityNotFoundException('Event not found on summit.');
            }

            if (!Summit::allowToSee($event, $member))
                throw new EntityNotFoundException('Event not found on summit.');

            if (!$event->hasRSVPTemplate()) {
                throw new EntityNotFoundException('Event not found on summit.');
            }

            // add to schedule the RSVP event
            if (!$member->isOnSchedule($event)) {
                throw new EntityNotFoundException('Event not found on summit.');
            }

            $rsvp = $member->getRsvpByEvent($event->getId());

            if (is_null($rsvp))
                throw new ValidationException
                (
                    sprintf
                    (
                        "Member %s did not submitted an rsvp for event %s on summit %s.",
                        $member->getId(),
                        $event_id,
                        $summit->getId()
                    )
                );

            // update RSVP

            $rsvp = SummitRSVPFactory::populate($rsvp, $event, $member, $data);

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
        $this->tx_service->transaction(function () use ($summit, $member, $event_id) {

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

            $summit_event = $this->event_repository->getByIdExclusiveLock($event_id);

            if (!$summit_event instanceof SummitEvent) {
                throw new EntityNotFoundException('Event not found on summit.');
            }

            if ($summit_event->getSummitId() != $summit->getId()) {
                throw new EntityNotFoundException('Event not found on summit.');
            }

            if (!Summit::allowToSee($summit_event, $member))
                throw new EntityNotFoundException('Event not found on summit.');

            $rsvp = $member->getRsvpByEvent($event_id);

            if (is_null($rsvp))
                throw new ValidationException(sprintf("RSVP for event id %s does not exist for your member.", $event_id));

            $current_seat_type = $rsvp->getSeatType();

            if($current_seat_type === RSVP::SeatTypeRegular) {
                // we need to get the first on WAIT list and move it to REGULAR LIST
                // get the first one on SeatTypeWaitList
                $rsvp_on_wait = $summit_event->getFirstRSVPOnWaitList();
                if(!is_null($rsvp_on_wait)) {
                    Log::debug
                    (
                        sprintf
                        (
                            "SummitRSVPService::unRSVPEvent got RSVP %s at WAIT LIST moving it to REGULAR ...",
                            $rsvp_on_wait->getId()
                        )
                    );
                    $rsvp_on_wait->upgradeToRegularSeatType();

                    Event::dispatch(new RSVPUpdated($rsvp_on_wait));
                }
            }

            $this->rsvp_repository->delete($rsvp);

            Event::dispatch(new RSVPDeleted($rsvp));
        });
    }

}