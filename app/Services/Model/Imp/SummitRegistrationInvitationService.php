<?php namespace App\Services\Model\Imp;
/**
 * Copyright 2020 OpenStack Foundation
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

use App\Jobs\Emails\Registration\Invitations\InviteSummitRegistrationEmail;
use App\Jobs\Emails\Registration\Invitations\ProcessRegistrationInvitationsJob;
use App\Jobs\Emails\Registration\Invitations\ReInviteSummitRegistrationEmail;
use App\Models\Foundation\Summit\Factories\SummitRegistrationInvitationFactory;
use App\Models\Foundation\Summit\Repositories\ISummitRegistrationInvitationRepository;
use App\Services\Apis\IExternalUserApi;
use App\Services\Model\AbstractService;
use App\Services\Model\dto\ExternalUserDTO;
use App\Services\Model\IMemberService;
use App\Services\Model\ISummitRegistrationInvitationService;
use App\Services\Model\ITagService;
use App\Services\Utils\CSVReader;
use Google\Service\Classroom\Registration;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use libs\utils\ITransactionService;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use models\main\IMemberRepository;
use models\main\ITagRepository;
use models\main\Member;
use models\summit\ISummitRepository;
use models\summit\Summit;
use models\summit\SummitRegistrationInvitation;
use utils\Filter;
use utils\FilterElement;
use utils\PagingInfo;

/**
 * Class SummitRegistrationInvitationService
 * @package App\Services\Model\Imp
 */
final class SummitRegistrationInvitationService
    extends AbstractService
    implements ISummitRegistrationInvitationService

{
    /**
     * @var IMemberRepository
     */
    private $member_repository;

    /**
     * @var IExternalUserApi
     */
    private $external_user_api;

    /**
     * @var IMemberService
     */
    private $member_service;

    /**
     * @var ISummitRegistrationInvitationRepository
     */
    private $invitation_repository;

    /**
     * @var ISummitRepository
     */
    private $summit_repository;

    /**
     * @var ITagRepository
     */
    private $tag_repository;

    /**
     * @var ITagService
     */
    private $tag_service;

    /**
     * @param IExternalUserApi $external_user_api
     * @param ITagService $tag_service
     * @param IMemberService $member_service
     * @param ISummitRegistrationInvitationRepository $invitation_repository
     * @param IMemberRepository $member_repository
     * @param ISummitRepository $summit_repository
     * @param ITagRepository $tag_repository
     * @param ITransactionService $tx_service
     */
    public function __construct
    (
        IExternalUserApi                        $external_user_api,
        ITagService                             $tag_service,
        IMemberService                          $member_service,
        ISummitRegistrationInvitationRepository $invitation_repository,
        IMemberRepository                       $member_repository,
        ISummitRepository                       $summit_repository,
        ITagRepository                          $tag_repository,
        ITransactionService                     $tx_service
    )
    {
        parent::__construct($tx_service);
        $this->member_repository = $member_repository;
        $this->summit_repository = $summit_repository;
        $this->external_user_api = $external_user_api;
        $this->invitation_repository = $invitation_repository;
        $this->member_service = $member_service;
        $this->tag_repository = $tag_repository;
        $this->tag_service = $tag_service;
    }

    /**
     * @param Summit $summit
     * @param UploadedFile $csv_file
     * @param array $payload
     * @return void
     * @throws ValidationException
     */
    public function importInvitationData(Summit $summit, UploadedFile $csv_file, array $payload=[]): void
    {
        Log::debug(sprintf("SummitRegistrationInvitationService::importInvitationData - summit %s", $summit->getId()));

        $allowed_extensions = ['txt'];

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
         * first_name (mandatory)
         * last_name (mandatory)
         * allowed_ticket_types (optional)
         * tags (optional)
         ***********************************************************/

        if (!$reader->hasColumn("email"))
            throw new ValidationException("File is missing email column.");
        if (!$reader->hasColumn("first_name"))
            throw new ValidationException("File is missing first_name column.");
        if (!$reader->hasColumn("last_name"))
            throw new ValidationException("File is missing last_name column.");

        foreach ($reader as $idx => $row) {
            try {

                Log::debug(sprintf("SummitRegistrationInvitationService::importInvitationData processing row %s", json_encode($row)));
                if (isset($row['allowed_ticket_types']) && is_string($row['allowed_ticket_types'])) {
                    $row['allowed_ticket_types'] = empty($row['allowed_ticket_types']) ? [] : explode('|', $row['allowed_ticket_types']);
                }
                if (isset($row['tags']) && is_string($row['tags'])) {
                    $row['tags'] = empty($row['tags']) ? [] : explode('|', $row['tags']);
                }

                $email = trim($row['email']);
                $former_invitation = $summit->getSummitRegistrationInvitationByEmail($email);
                $row['acceptance_criteria'] = $payload['acceptance_criteria'] ?? SummitRegistrationInvitation::AcceptanceCriteria_AllTicketTypes;
                if (!is_null($former_invitation)) {
                    $this->update($summit, $former_invitation->getId(), $row);
                } else {
                    $this->add($summit, $row);
                }
            } catch (\Exception $ex) {
                Log::warning($ex);
                $summit = $this->summit_repository->getById($summit->getId());
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function delete(Summit $summit, int $invitation_id): void
    {
        $this->tx_service->transaction(function () use ($summit, $invitation_id) {
            $invitation = $summit->getSummitRegistrationInvitationById($invitation_id);
            if (is_null($invitation)) {
                throw new EntityNotFoundException("Invitation not found.");
            }

            $summit->removeRegistrationInvitation($invitation);
        });
    }

    /**
     * @inheritDoc
     */
    public function add(Summit $summit, array $payload): SummitRegistrationInvitation
    {
        return $this->tx_service->transaction(function () use ($summit, $payload) {

            $email = trim($payload['email']);
            Log::debug(sprintf("SummitRegistrationInvitationService::add trying to add email %s", $email));
            $allowed_ticket_types = $payload['allowed_ticket_types'] ?? [];
            $former_invitation = $summit->getSummitRegistrationInvitationByEmail($email);
            if (!is_null($former_invitation)) {
                throw new ValidationException(sprintf("Email %s already has been invited for summit %s", $email, $summit->getId()));
            }

            $invitation = SummitRegistrationInvitationFactory::build($payload);
            foreach ($allowed_ticket_types as $ticket_type_id) {
                $ticket_type = $summit->getTicketTypeById(intval($ticket_type_id));
                Log::debug(sprintf("SummitRegistrationInvitationService::add trying to add ticket %s for invitation email %s", $ticket_type_id, $email));
                if (is_null($ticket_type)) {
                    throw new ValidationException
                    (
                        sprintf
                        (
                            "SummitRegistrationInvitationService::add ticket type %s does not exists on summit %s.",
                            $ticket_type_id,
                            $summit->getId()
                        )
                    );
                }
                $invitation->addTicketType($ticket_type);
            }

            // tags
            if (isset($payload['tags'])) {
                $invitation->clearTags();

                foreach ($payload['tags'] as $tag_value) {
                    $tag = $this->tag_repository->getByTag($tag_value);
                    if (is_null($tag)) {
                        $tag = $this->tag_service->addTag(['tag' => $tag_value]);
                    }
                    $invitation->addTag($tag);
                }
            }

            try {
                $invitation = $this->setInvitationMember($invitation, $email);
            }
            catch (\Exception $ex){
                Log::warning($ex);
            }

            Log::debug(sprintf("SummitRegistrationInvitationService::add adding invitation for email %s to summit %s", $email, $summit->getName()));
            $summit->addRegistrationInvitation($invitation);

            return $invitation;
        });
    }

    /**
     * @param SummitRegistrationInvitation $invitation
     * @param string $email
     * @return SummitRegistrationInvitation
     * @throws \Exception
     */
    private function setInvitationMember(SummitRegistrationInvitation $invitation, string $email): SummitRegistrationInvitation
    {
        return $this->tx_service->transaction(function () use ($invitation, $email) {
            try {
                // try to get local user
                $member = $this->member_repository->getByEmail($email);
                // try to get an user externally , user does not exist locally
                if (is_null($member)) {
                    // check if user exists by email at idp
                    Log::debug(sprintf("SummitRegistrationInvitationService::setInvitationMember - trying to get member %s from user api", $email));
                    $user = $this->external_user_api->getUserByEmail($email);
                    // check if primary email is the same if not disregard
                    $primary_email = is_null($user) ? null : $user['email'] ?? null;
                    if (strcmp(strtolower($primary_email), strtolower($email)) !== 0) {
                        Log::debug
                        (
                            sprintf
                            (
                                "SummitRegistrationInvitationService::setInvitationMember primary email %s differs from order owner email %s",
                                $primary_email,
                                $email
                            )
                        );

                        // email are not equals , then is not the user bc primary emails differs ( could be a
                        // match on a secondary email)
                        $user = null; // set null on user and proceed to emit a registration request.
                    }

                    if (!is_null($user)) {
                        Log::debug
                        (
                            sprintf
                            (
                                "SummitRegistrationInvitationService::setInvitationMember - Creating a local user for %s",
                                $email
                            )
                        );
                        $external_id = $user['id'];
                        try {

                            // we have an user on idp
                            // possible race condition
                            $member = $this->member_service->registerExternalUser
                            (
                                new ExternalUserDTO
                                (
                                    $external_id,
                                    $user['email'],
                                    $user['first_name'],
                                    $user['last_name'],
                                    boolval($user['active']),
                                    boolval($user['email_verified'])
                                )
                            );
                        } catch (\Exception $ex) {
                            Log::warning($ex);
                            // race condition lost
                            $member = $this->member_repository->getByExternalIdExclusiveLock(intval($external_id));
                            $invitation = $this->invitation_repository->getByIdExclusiveLock($invitation->getId());
                        }
                    }
                }

                if (!is_null($member))
                    $invitation->setMember($member);
            }
            catch (\Exception $ex){
                Log::warning($ex);
            }
            return $invitation;
        });
    }

    /**
     * @inheritDoc
     */
    public function update(Summit $summit, int $invitation_id, array $payload): SummitRegistrationInvitation
    {
        return $this->tx_service->transaction(function () use ($summit, $payload, $invitation_id) {
            $invitation = $summit->getSummitRegistrationInvitationById($invitation_id);
            if (is_null($invitation))
                throw new EntityNotFoundException(sprintf("Invitation %s not found at Summit %s", $invitation_id, $summit->getId()));

            if (isset($payload['email'])) {
                $email = trim($payload['email']);
                $former_invitation = $summit->getSummitRegistrationInvitationByEmail($email);
                if (!is_null($former_invitation) && $former_invitation->getId() !== $invitation_id) {
                    throw new ValidationException(sprintf("Email %s already has been invited for summit %s", $email, $summit->getId()));
                }
            }

            if (isset($payload['email'])) {
                $email = trim($payload['email']);
                try {
                    $invitation = $this->setInvitationMember($invitation, $email);
                } catch (\Exception $ex) {
                    Log::warning($ex);
                }
            }

            $is_formerly_accepted = $invitation->isAccepted();
            $allowed_ticket_types = $payload['allowed_ticket_types'] ?? [];
            if (count($allowed_ticket_types) > 0) {
                foreach ($allowed_ticket_types as $ticket_type_id) {
                    $ticket_type = $summit->getTicketTypeById(intval($ticket_type_id));
                    if (is_null($ticket_type)) {
                        throw new ValidationException
                        (
                            sprintf
                            (
                                "SummitRegistrationInvitationService::add ticket type %s does not exists on summit %s.",
                                $ticket_type_id,
                                $summit->getId()
                            )
                        );
                    }
                    $invitation->addTicketType($ticket_type);
                }
                // remove missing ones
                $to_remove = [];
                foreach ($invitation->getTicketTypes() as $invitation_ticket_type){
                    if(!in_array($invitation_ticket_type->getId(), $allowed_ticket_types)){
                        $to_remove[] = $invitation_ticket_type;
                    }
                }
                foreach ($to_remove as $ticket_type_to_remove){
                    $invitation->removeTicketType($ticket_type_to_remove);
                }
            }

            if($invitation->isAccepted() != $is_formerly_accepted){
               // it changed its state bc the ticket types update
                $payload['is_accepted'] = $invitation->isAccepted();
            }

            // tags
            if (isset($payload['tags'])) {
                $invitation->clearTags();

                foreach ($payload['tags'] as $tag_value) {
                    $tag = $this->tag_repository->getByTag($tag_value);
                    if (is_null($tag)) {
                        $tag = $this->tag_service->addTag(['tag' => $tag_value]);
                    }
                    $invitation->addTag($tag);
                }
            }

            return SummitRegistrationInvitationFactory::populate($invitation, $payload);
        });
    }

    /**
     * @param Member $current_member
     * @param string $token
     * @return SummitRegistrationInvitation
     * @throws \Exception
     */
    public function getInvitationByToken(Member $current_member, string $token): SummitRegistrationInvitation
    {
        return $this->tx_service->transaction(function () use ($current_member, $token) {

            $invitation = $this->invitation_repository->getByHashExclusiveLock(SummitRegistrationInvitation::HashConfirmationToken($token));

            if (is_null($invitation))
                throw new EntityNotFoundException("Invitation not found.");
            Log::debug(sprintf("got invitation %s for email %s", $invitation->getId(), $invitation->getEmail()));
            if (strtolower($invitation->getEmail()) !== strtolower($current_member->getEmail()))
                throw new ValidationException(sprintf(
                    "This invitation was sent to %s but you logged in as %s."
                    ." To be able to register for this event, sign out and then RSVP from the email invite and then log in with your primary email address."
                    ." Email <a href='mailto:%s'>%s</a> for additional troubleshooting."
                    ,
                    $invitation->getEmail(),
                    $current_member->getEmail(),
                    $invitation->getSummit()->getSupportEmail(),
                    $invitation->getSummit()->getSupportEmail()));

            $invitation->setMember($current_member);

            if ($invitation->isAccepted()) {
                throw new ValidationException("This Invitation is already accepted.");
            }

            return $invitation;
        });
    }

    /**
     * @param Summit $summit
     * @param string $email
     * @return SummitRegistrationInvitation|null
     * @throws \Exception
     */
    public function getInvitationByEmail(Summit $summit, string $email): ?SummitRegistrationInvitation
    {
        return $this->tx_service->transaction(function () use ($summit, $email) {
            return $summit->getSummitRegistrationInvitationByEmail($email);
        });
    }

    /**
     * @inheritDoc
     */
    public function deleteAll(Summit $summit): void
    {
        $this->tx_service->transaction(function () use ($summit) {
            $summit->clearRegistrationInvitations();
        });
    }

    /**
     * @param Summit $summit
     * @param array $payload
     * @param Filter|null $filter
     */
    public function triggerSend(Summit $summit, array $payload, $filter = null): void
    {
        ProcessRegistrationInvitationsJob::dispatch($summit, $payload, $filter);
    }

    /**
     * @inheritDoc
     */
    public function send(int $summit_id, array $payload, Filter $filter = null): void
    {
        $flow_event = trim($payload['email_flow_event']);
        Log::debug(sprintf("SummitRegistrationInvitationService::send summit id %s flow_event %s filter %s", $summit_id, $flow_event, is_null($filter) ? '' : $filter->__toString()));
        $done = isset($payload['invitations_ids']); // we have provided only ids and not a criteria
        $page = 1;
        $count = 0;
        $maxPageSize = 100;

        $test_email_recipient = null;
        if(isset($payload['test_email_recipient'])) {
            Log::debug
            (
                sprintf
                (
                    "SummitRegistrationInvitationService::send summit id %s setting test_email_recipient %s",
                    $summit_id,
                    $payload['test_email_recipient']
                )
            );

            $test_email_recipient = $payload['test_email_recipient'];
        }

        do{

            Log::debug(sprintf("SummitRegistrationInvitationService::send summit id %s flow_event %s filter %s processing page %s", $summit_id, $flow_event, is_null($filter) ? '' : $filter->__toString(), $page));

            $ids = $this->tx_service->transaction(function () use ($summit_id, $payload, $filter, $page, $maxPageSize) {
                if (isset($payload['invitations_ids'])) {
                    Log::debug(sprintf("SummitRegistrationInvitationService::send summit id %s invitations_ids %s", $summit_id, json_encode($payload['invitations_ids'])));
                    return $payload['invitations_ids'];
                }
                Log::debug(sprintf("SummitRegistrationInvitationService::send summit id %s getting by filter", $summit_id));
                if (is_null($filter)) {
                    $filter = new Filter();
                }
                if (!$filter->hasFilter("summit_id"))
                    $filter->addFilterCondition(FilterElement::makeEqual('summit_id', $summit_id));
                Log::debug(sprintf("SummitRegistrationInvitationService::send page %s", $page));
                if($filter->hasFilter("is_sent")){
                    $isSentFilter = $filter->getUniqueFilter("is_sent");
                    $is_sent = $isSentFilter->getBooleanValue();
                    Log::debug(sprintf("SummitRegistrationInvitationService::send is_sent filter value %b", $is_sent));
                    if(!$is_sent) {
                        // we need to reset the page bc the page processing will mark the current page as "sent"
                        // and adding an offset will move the cursor forward, leaving next round of not send out of the current process
                        Log::debug("SummitRegistrationInvitationService::send resetting page bc is_sent filter is false");
                        $page = 1;
                    }
                }
                return $this->invitation_repository->getAllIdsByPage(new PagingInfo($page, $maxPageSize), $filter);
            });

            Log::debug(sprintf("SummitRegistrationInvitationService::send summit id %s flow_event %s filter %s page %s got %s records", $summit_id, $flow_event, is_null($filter) ? '' : $filter->__toString(), $page, count($ids)));
            if (!count($ids)) {
                // if we are processing a page , then break it
                Log::debug(sprintf("SummitRegistrationInvitationService::send summit id %s page is empty, ending processing.", $summit_id));
                break;
            }

            foreach ($ids as $invitation_id) {

                $res = $this->tx_service->transaction(function () use ($flow_event, $invitation_id) {

                    Log::debug(sprintf("SummitRegistrationInvitationService::send processing invitation id  %s", $invitation_id));

                    $invitation = $this->invitation_repository->getByIdExclusiveLock(intval($invitation_id));
                    if (is_null($invitation) || !$invitation instanceof SummitRegistrationInvitation) return null;

                    $summit = $invitation->getSummit();

                    while (true) {
                        $invitation->generateConfirmationToken();
                        $former_invitation = $this->invitation_repository->getByHashAndSummit($invitation->getHash(), $summit);
                        if (is_null($former_invitation) || $former_invitation->getId() == $invitation->getId()) break;
                    }

                    return $invitation;
                });

                // send email
                if ($flow_event == InviteSummitRegistrationEmail::EVENT_SLUG && !is_null($res))
                    InviteSummitRegistrationEmail::dispatch($res, $test_email_recipient);
                if ($flow_event == ReInviteSummitRegistrationEmail::EVENT_SLUG && !is_null($res))
                    ReInviteSummitRegistrationEmail::dispatch($res, $test_email_recipient);
                $count++;
            }

            $page++;

        }while(!$done);

        Log::debug(sprintf("SummitRegistrationInvitationService::send summit id %s flow_event %s filter %s had processed %s records", $summit_id, $flow_event, is_null($filter) ? '' : $filter->__toString(), $count));
    }
}