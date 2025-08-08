<?php namespace App\Services\Model\Imp;

use App\Models\Foundation\Summit\Events\RSVP\Repositories\IRSVPInvitationRepository;
use App\Models\Foundation\Summit\Events\RSVP\RSVPInvitation;
use App\Services\ISummitRSVPInvitationService;
use App\Services\Model\AbstractService;
use App\Services\Model\ISummitRSVPService;
use App\Services\Utils\CSVReader;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use libs\utils\ITransactionService;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use models\summit\ISummitEventRepository;
use models\summit\SummitEvent;
use utils\Filter;

class SummitRSVPInvitationService
    extends AbstractService
    implements ISummitRSVPInvitationService
{

    private ISummitEventRepository $summit_event_repository;

    private IRSVPInvitationRepository $invitation_repository;

    private ISummitRSVPService $rsvp_service;

    /**
     * @param ISummitEventRepository $summit_event_repository
     * @param IRSVPInvitationRepository $invitation_repository
     * @param ISummitRSVPService $rsvp_service
     * @param ITransactionService $transaction_service
     */
    public function __construct(
        ISummitEventRepository $summit_event_repository,
        IRSVPInvitationRepository $invitation_repository,
        ISummitRSVPService $rsvp_service,
        ITransactionService $transaction_service
    ){
        parent::__construct($transaction_service);
        $this->summit_event_repository = $summit_event_repository;
        $this->invitation_repository = $invitation_repository;
        $this->rsvp_service = $rsvp_service;
    }
    /**
     * @param SummitEvent $summit_event
     * @param UploadedFile $csv_file
     * @param array $payload
     * @return void
     * @throws ValidationException
     */
    public function importInvitationData(SummitEvent $summit_event, UploadedFile $csv_file, array $payload = []): void
    {
        Log::debug(sprintf("SummitRSVPInvitationService::importInvitationData - event %s", $summit_event->getId()));

        $allowed_extensions = ['txt','csv'];

        if (!in_array($csv_file->extension(), $allowed_extensions)) {
            throw new ValidationException("File does not has a valid extension ('csv').");
        }

        $csv_data = File::get($csv_file->getRealPath());

        if (empty($csv_data))
            throw new ValidationException("File content is empty.");

        $reader = CSVReader::buildFrom($csv_data);

        // check needed columns (headers names)
        /***********************************************************
         * columns
         * email (mandatory)
         ***********************************************************/

        if (!$reader->hasColumn("email"))
            throw new ValidationException("File is missing email column.");

        foreach ($reader as $idx => $row) {
            try {

                Log::debug(sprintf("SummitRSVPInvitationService::importInvitationData processing row %s", json_encode($row)));

                $email = trim($row['email']);
                $summit  = $summit_event->getSummit();
                $attendee = $summit->getAttendeeByEmail($email);

                if(is_null($attendee)){
                    Log::warning
                    (
                        sprintf
                        (
                            "SummitRSVPInvitationService::importInvitationData attendee %s does not exists at summit %s",
                            $email,
                            $summit->getId()
                        )
                    );
                    continue;
                }

                $former_invitation = $summit_event->getRSVPInvitationByInvitee($attendee);
                $row['attendee_id'] = $attendee->getId();
                if (!is_null($former_invitation)) {
                    $this->update($summit_event, $former_invitation->getId(), $row);
                } else {
                    $this->add($summit_event, $row);
                }
            } catch (\Exception $ex) {
                Log::warning($ex);
                $summit_event = $this->summit_event_repository->getById($summit_event->getId());
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function delete(SummitEvent $summit_event, int $invitation_id): void
    {
        // TODO: Implement delete() method.
    }

    /**
     * @inheritDoc
     */
    public function deleteAll(SummitEvent $summit_event): void
    {
        // TODO: Implement deleteAll() method.
    }

    /**
     * @inheritDoc
     */
    public function add(SummitEvent $summit_event, array $payload): RSVPInvitation
    {
        return $this->tx_service->transaction(function () use ($summit_event, $payload) {
            $attendee_id = intval($payload['attendee_id']);
            Log::debug(sprintf("SummitRSVPInvitationService::add trying to add process attendee id %s.", $attendee_id));
            $summit = $summit_event->getSummit();
            $attendee = $summit->getAttendeeById($attendee_id);
            if(is_null($attendee))
                throw new EntityNotFoundException("Attendee not found.");
            return $summit_event->addRSVPInvitation($attendee);
        });
    }

    /**
     * @inheritDoc
     */
    public function update(SummitEvent $summit_event, int $invitation_id, array $payload): RSVPInvitation
    {
        // TODO: Implement update() method.
    }

    /**
     * @inheritDoc
     */
    public function getInvitationByToken(string $token): RSVPInvitation
    {

        return $this->tx_service->transaction(function () use ($token) {

            $invitation = $this->invitation_repository->getByHashExclusiveLock(RSVPInvitation::HashConfirmationToken($token));

            if (is_null($invitation))
                throw new EntityNotFoundException("Invitation not found.");

            $invitee = $invitation->getInvitee();
            Log::debug(sprintf("got invitation %s for email %s", $invitation->getId(), $invitee->getEmail()));

            if (!$invitation->isPending()) {
                throw new ValidationException("This Invitation is already accepted.");
            }

            return $invitation;
        });
    }

    /**
     * @inheritDoc
     */
    public function acceptInvitationBySummitAndToken(string $token): RSVPInvitation
    {
        return $this->tx_service->transaction(function () use ($token) {

            $invitation = $this->invitation_repository->getByHashExclusiveLock(RSVPInvitation::HashConfirmationToken($token));

            if (is_null($invitation))
                throw new EntityNotFoundException("Invitation not found.");

            $invitee = $invitation->getInvitee();
            Log::debug(sprintf("got invitation %s for email %s", $invitation->getId(), $invitee->getEmail()));

            if(!$invitee->hasMember())
                throw new EntityNotFoundException("Attendee has not Member associated with it");

            if (!$invitation->isPending()) {
                throw new ValidationException("This Invitation is already accepted.");
            }

            if(!$invitee->hasTicketsPaidTickets())
                throw new ValidationException("Attendee has no valid tickets.");

            $invitation->markAsAccepted();

            $rsvp = $this->rsvp_service->addRSVP(
                $invitation->getEvent()->getSummit(),
                $invitee->getMember(),
                $invitation->getEvent()->getId(),
            );
            // associate invitation with RSVP
            return $invitation;
        });
    }

    /**
     * @inheritDoc
     */
    public function rejectInvitationBySummitAndToken(string $token): RSVPInvitation
    {
        return $this->tx_service->transaction(function () use ($token) {

            $invitation = $this->invitation_repository->getByHashExclusiveLock(RSVPInvitation::HashConfirmationToken($token));

            if (is_null($invitation))
                throw new EntityNotFoundException("Invitation not found.");

            $invitee = $invitation->getInvitee();
            Log::debug(sprintf("got invitation %s for email %s", $invitation->getId(), $invitee->getEmail()));

            if(!$invitee->hasMember())
                throw new EntityNotFoundException("Attendee has not Member associated with it");

            if (!$invitation->isPending()) {
                throw new ValidationException("This Invitation is already accepted.");
            }

            $invitation->markAsRejected();

            return $invitation;
        });
    }

    /**
     * @inheritDoc
     */
    public function triggerSend(SummitEvent $summit_event, array $payload, $filter = null): void
    {
        // TODO: Implement triggerSend() method.
    }

    /**
     * @inheritDoc
     */
    public function send(int $event_id, array $payload, Filter $filter = null): void
    {
        // TODO: Implement send() method.
    }
}