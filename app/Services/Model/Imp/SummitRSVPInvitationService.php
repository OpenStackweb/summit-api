<?php namespace App\Services\Model\Imp;

use App\Jobs\Emails\Schedule\RSVP\ProcessRSVPInvitationsJob;
use App\Jobs\Emails\Schedule\RSVP\ReRSVPInviteEmail;
use App\Jobs\Emails\Schedule\RSVP\RSVPInvitationExcerptEmail;
use App\Jobs\Emails\Schedule\RSVP\RSVPInviteEmail;
use App\Models\Foundation\Summit\Events\RSVP\Repositories\IRSVPInvitationRepository;
use App\Models\Foundation\Summit\Events\RSVP\RSVPInvitation;
use App\Services\ISummitRSVPInvitationService;
use App\Services\Model\AbstractService;
use App\Services\Model\Imp\Traits\ParametrizedSendEmails;
use App\Services\Model\ISummitRSVPService;
use App\Services\Utils\CSVReader;
use App\Services\utils\IEmailExcerptService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use libs\utils\ITransactionService;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use models\main\Member;
use models\summit\ISummitEventRepository;
use models\summit\RSVP;
use models\summit\SummitEvent;
use utils\Filter;
use utils\FilterElement;

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
        ISummitEventRepository    $summit_event_repository,
        IRSVPInvitationRepository $invitation_repository,
        ISummitRSVPService        $rsvp_service,
        ITransactionService       $transaction_service
    )
    {
        parent::__construct($transaction_service);
        $this->summit_event_repository = $summit_event_repository;
        $this->invitation_repository = $invitation_repository;
        $this->rsvp_service = $rsvp_service;
    }

    /**
     * @param SummitEvent $summit_event
     * @param UploadedFile $csv_file
     * @return void
     * @throws ValidationException
     */
    public function importInvitationData(SummitEvent $summit_event, UploadedFile $csv_file): void
    {
        Log::debug(sprintf("SummitRSVPInvitationService::importInvitationData - event %s", $summit_event->getId()));

        $allowed_extensions = ['txt', 'csv'];

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
        $errors = [];
        foreach ($reader as $idx => $row) {
            try {

                Log::debug(sprintf("SummitRSVPInvitationService::importInvitationData processing row %s", json_encode($row)));

                $email = trim($row['email']);
                $summit = $summit_event->getSummit();
                $attendee = $summit->getAttendeeByEmail($email);

                if (is_null($attendee)) {
                    Log::warning
                    (
                        sprintf
                        (
                            "SummitRSVPInvitationService::importInvitationData attendee %s does not exists at summit %s",
                            $email,
                            $summit->getId()
                        )
                    );
                    $errors[] =
                        sprintf
                        (
                            "Attendee %s does not exists at summit %s",
                            $email,
                            $summit->getId()
                        );
                    continue;
                }

                $former_invitation = $summit_event->getRSVPInvitationByInvitee($attendee);
                $row['invitee_id'] = $attendee->getId();
                if (!is_null($former_invitation))
                    throw new ValidationException
                    (
                        sprintf
                        (
                            "Attendee %s already has a RSVP Invitation (%s).",
                            $attendee->getId(), $former_invitation->getId()
                        )
                    );
                $this->add($summit_event, $row);

            } catch (\Exception $ex) {
                Log::warning($ex);
                $errors[] = $ex->getMessage();
                $summit_event = $this->summit_event_repository->getById($summit_event->getId());
            }
        }
        if (count($errors) > 0) {
            throw new ValidationException($errors);
        }
    }

    /**
     * @param SummitEvent $summit_event
     * @param int $invitation_id
     * @return void
     * @throws \Exception
     */
    public function delete(SummitEvent $summit_event, int $invitation_id): void
    {
        $this->tx_service->transaction(function () use ($summit_event, $invitation_id) {
            $invitation = $this->invitation_repository->getById($invitation_id);
            if (!$invitation instanceof RSVPInvitation) {
                throw new EntityNotFoundException("Invitation not found.");
            }
            if ($invitation->isAccepted()) {
                throw new ValidationException("Invitation is accepted.");
            }
            $this->invitation_repository->delete($invitation);
        });
    }

    /**
     * @param SummitEvent $summit_event
     * @param Member $current_member
     * @return void
     * @throws \Exception
     */
    public function deleteAll(SummitEvent $summit_event, Member $current_member): void
    {
        $this->tx_service->transaction(function () use ($summit_event, $current_member) {
            Log::debug
            (
                sprintf
                (
                    "SummitRSVPInvitationService::deleteAll event %s by user %s(%s)",
                    $summit_event->getId(),
                    $current_member->getEmail(),
                    $current_member->getId()
                )
            );
            $summit_event->clearRSVPInvitations();
        });
    }

    /**
     * @inheritDoc
     */
    public function add(SummitEvent $summit_event, array $payload): array
    {

        return $this->tx_service->transaction(function () use ($summit_event, $payload) {
            $invitee_ids = $payload['invitee_ids'] ?? [];
            $invitations = [];
            foreach ($invitee_ids as $invitee_id) {
                try {
                    Log::debug(sprintf("SummitRSVPInvitationService::add trying to add process invitee id %s.", $invitee_id));
                    $summit = $summit_event->getSummit();
                    $attendee = $summit->getAttendeeById($invitee_id);
                    if (is_null($attendee))
                        throw new EntityNotFoundException("Attendee not found.");
                    $former_invitation = $summit_event->getRSVPInvitationByInvitee($attendee);
                    if (!is_null($former_invitation))
                        throw new ValidationException
                        (
                            sprintf
                            (
                                "Attendee %s already has a RSVP Invitation (%s).",
                                $attendee->getId(), $former_invitation->getId()
                            )
                        );
                    $invitations[] = $summit_event->addRSVPInvitation($attendee);
                } catch (\Exception $ex) {
                    Log::warning($ex);
                }
            }
            return $invitations;
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
     * @param SummitEvent $event
     * @param string $token
     * @return RSVPInvitation
     * @throws \Exception
     */
    public function getInvitationBySummitEventAndToken(SummitEvent $event, string $token): RSVPInvitation
    {

        return $this->tx_service->transaction(function () use ($event, $token) {

            $invitation = $this->invitation_repository->getByHashAndSummitEvent(RSVPInvitation::HashConfirmationToken($token), $event);

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
     * @param SummitEvent $event
     * @param string $token
     * @return RSVPInvitation
     * @throws \Exception
     */
    public function acceptInvitationBySummitEventAndToken(SummitEvent $event, string $token): RSVPInvitation
    {
        return $this->tx_service->transaction(function () use ($event, $token) {

            $invitation = $this->invitation_repository->getByHashAndSummitEvent(RSVPInvitation::HashConfirmationToken($token), $event);

            if (is_null($invitation))
                throw new EntityNotFoundException("Invitation not found.");

            $invitee = $invitation->getInvitee();
            Log::debug(sprintf("got invitation %s for email %s", $invitation->getId(), $invitee->getEmail()));

            if (!$invitee->hasMember())
                throw new EntityNotFoundException("Attendee has not Member associated with it");

            if (!$invitation->isPending()) {
                throw new ValidationException("This Invitation is already accepted.");
            }

            if (!$invitee->hasTicketsPaidTickets())
                throw new ValidationException("Attendee has no valid tickets.");


            $event = $invitation->getEvent();
            $summit = $event->getSummit();
            $rsvp = $this->rsvp_service->rsvpEvent(
                $summit,
                $invitee->getMember(),
                $event->getId(),
            );

            $invitation->markAsAcceptedWithRSVP($rsvp);
            $rsvp->setActionSource(RSVP::ActionSource_Invitation);
            // associate invitation with RSVP
            return $invitation;
        });
    }

    /**
     * @param SummitEvent $event
     * @param string $token
     * @return RSVPInvitation
     * @throws \Exception
     */
    public function rejectInvitationBySummitEventAndToken(SummitEvent $event, string $token): RSVPInvitation
    {
        return $this->tx_service->transaction(function () use ($event, $token) {

            $invitation = $this->invitation_repository->getByHashAndSummitEvent(RSVPInvitation::HashConfirmationToken($token), $event);

            if (is_null($invitation))
                throw new EntityNotFoundException("Invitation not found.");

            $invitee = $invitation->getInvitee();
            Log::debug(sprintf("got invitation %s for email %s", $invitation->getId(), $invitee->getEmail()));

            if (!$invitee->hasMember())
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
        ProcessRSVPInvitationsJob::dispatch($summit_event, $payload, $filter);
    }

    use ParametrizedSendEmails;

    /**
     * @param int $event_id
     * @param array $payload
     * @param Filter|null $filter
     * @return void
     * @throws ValidationException
     */
    public function send(int $event_id, array $payload, Filter $filter = null): void
    {
        $this->_sendEmails(
            $event_id,
            $payload,
            "invitations",
            function ($root_entity, $paging_info, $filter, $resetPage) {

                if (!$filter->hasFilter("event_id"))
                    $filter->addFilterCondition(FilterElement::makeEqual('event_id', $root_entity->getId()));

                if ($filter->hasFilter("is_sent")) {
                    $isSentFilter = $filter->getUniqueFilter("is_sent");
                    $is_sent = $isSentFilter->getBooleanValue();
                    Log::debug(sprintf("SummitRSVPInvitationService::send is_sent filter value %b", $is_sent));
                    if (!$is_sent && is_callable($resetPage)) {
                        // we need to reset the page bc the page processing will mark the current page as "sent"
                        // and adding an offset will move the cursor forward, leaving next round of not send out of the current process
                        Log::debug("SummitRSVPInvitationService::send resetting page bc is_sent filter is false");
                        $resetPage();
                    }
                }
                return $this->invitation_repository->getAllIdsByPage($paging_info, $filter);
            },
            function (
                $root_entity,
                $flow_event,
                $invitation_id,
                $test_email_recipient,
                $announcement_email_config,
                $filter,
                $onDispatchSuccess,
                $onDispatchError
            ) use ($payload) {
                try {
                    $this->tx_service->transaction(function () use (
                        $root_entity,
                        $flow_event,
                        $invitation_id,
                        $test_email_recipient,
                        $filter,
                        $onDispatchSuccess,
                        $onDispatchError,
                        $payload
                    ) {
                        $invitation = $this->tx_service->transaction(function () use ($flow_event, $invitation_id) {

                            Log::debug(sprintf("SummitRSVPInvitationService::send processing invitation id  %s", $invitation_id));

                            $invitation = $this->invitation_repository->getByIdExclusiveLock(intval($invitation_id));

                            if (!$invitation instanceof RSVPInvitation)
                                return null;

                            if ($invitation->isRejected()) {
                                Log::warning(sprintf("SummitRSVPInvitationService::send invitation %s is already rejected", $invitation_id));
                                return null;
                            }

                            $summit_event = $invitation->getEvent();

                            while (true) {
                                $invitation->generateConfirmationToken();
                                $former_invitation = $this->invitation_repository->getByHashAndSummitEvent($invitation->getHash(), $summit_event);
                                if (is_null($former_invitation) || $former_invitation->getId() == $invitation->getId()) break;
                            }

                            return $invitation;
                        });

                        $add_excerpt = false;

                        // send email
                        if (is_null($invitation)) return;
                        if ($flow_event == RSVPInviteEmail::EVENT_SLUG) {
                            RSVPInviteEmail::dispatch($invitation, $test_email_recipient);
                            $add_excerpt = true;
                        }


                        if ($flow_event == ReRSVPInviteEmail::EVENT_SLUG) {
                            ReRSVPInviteEmail::dispatch($invitation, $test_email_recipient);
                            $add_excerpt = true;
                        }

                        if ($add_excerpt) {
                            $onDispatchSuccess(
                                $invitation->getEmail(), IEmailExcerptService::EmailLineType, $flow_event
                            );
                        }

                        $invitation->markAsSent();
                    });
                } catch (\Exception $ex) {
                    Log::warning($ex);
                    $onDispatchError($ex->getMessage());
                }
            },
            function ($root_entity, $outcome_email_recipient, $report) {
                if (!$root_entity instanceof SummitEvent) return;
                RSVPInvitationExcerptEmail::dispatch($root_entity->getSummit(), $outcome_email_recipient, $report);
            },
            $filter,
            function () {
                return "SummitEvent";
            },
            function ($root_entity_id) {
                $summit_event = $this->summit_event_repository->getById($root_entity_id);
                if (!$summit_event instanceof SummitEvent) return null;
                return $summit_event;
            }
        );
    }
}