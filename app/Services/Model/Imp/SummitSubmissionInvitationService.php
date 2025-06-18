<?php namespace App\Services\Model\Imp;
/*
 * Copyright 2023 OpenStack Foundation
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

use App\Jobs\Emails\PresentationSubmissions\Invitations\InviteSubmissionEmail;
use App\Jobs\Emails\PresentationSubmissions\Invitations\ReInviteSubmissionEmail;
use App\Jobs\Emails\PresentationSubmissions\ProcessSubmissionsInvitationsJob;
use App\Models\Foundation\Summit\Factories\SummitSubmissionInvitationFactory;
use App\Models\Foundation\Summit\Repositories\ISummitSubmissionInvitationRepository;
use App\Services\Apis\IPasswordlessAPI;
use App\Services\Model\AbstractService;
use App\Services\Model\Imp\Traits\ParametrizedSendEmails;
use App\Services\Model\ISummitSubmissionInvitationService;
use App\Services\Model\ITagService;
use App\Services\Utils\CSVReader;
use App\Services\Utils\Facades\EmailExcerpt;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use libs\utils\ITransactionService;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use models\main\ITagRepository;
use models\summit\ISpeakerRepository;
use models\summit\ISummitRepository;
use models\summit\Summit;
use models\summit\SummitSubmissionInvitation;
use services\model\ISpeakerService;
use utils\Filter;
use utils\FilterElement;
use utils\PagingInfo;

/**
 * Class SummitSubmissionInvitationService
 * @package App\Services\Model\Imp
 */
final class SummitSubmissionInvitationService
    extends AbstractService
    implements ISummitSubmissionInvitationService
{

    /**
     * @var ISpeakerRepository
     */
    private $speaker_repository;

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
     * @var ISummitSubmissionInvitationRepository
     */
    private $invitation_repository;

    /**
     * @var ISpeakerService
     */
    private $speaker_service;

    /**
     * @var IPasswordlessAPI
     */
    private $passwordless_api;
    /**
     * @param ISummitRepository $summit_repository
     * @param ITagRepository $tag_repository
     * @param ISummitSubmissionInvitationRepository $invitation_repository
     * @param ISpeakerRepository $speaker_repository
     * @param ITagService $tag_service
     * @param ISpeakerService $speaker_service
     * @param IPasswordlessAPI $passwordless_api
     * @param ITransactionService $tx_service
     */
    public function __construct
    (
        ISummitRepository                     $summit_repository,
        ITagRepository                        $tag_repository,
        ISummitSubmissionInvitationRepository $invitation_repository,
        ISpeakerRepository                    $speaker_repository,
        ITagService                           $tag_service,
        ISpeakerService                       $speaker_service,
        IPasswordlessAPI                      $passwordless_api,
        ITransactionService                   $tx_service
    )
    {
        parent::__construct($tx_service);
        $this->summit_repository = $summit_repository;
        $this->tag_repository = $tag_repository;
        $this->invitation_repository = $invitation_repository;
        $this->speaker_repository = $speaker_repository;
        $this->tag_service = $tag_service;
        $this->passwordless_api = $passwordless_api;
        $this->speaker_service = $speaker_service;
    }

    /**
     * @param Summit $summit
     * @param UploadedFile $csv_file
     */
    public function importInvitationData(Summit $summit, UploadedFile $csv_file): void
    {
        Log::debug(sprintf("SummitSubmissionInvitationService::importInvitationData - summit %s", $summit->getId()));

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
         * first_name (mandatory)
         * last_name (mandatory)
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

                Log::debug(sprintf("SummitSubmissionInvitationService::importInvitationData processing row %s", json_encode($row)));

                if (isset($row['tags']) && is_string($row['tags'])) {
                    $row['tags'] = empty($row['tags']) ? [] : explode('|', $row['tags']);
                }

                $email = trim($row['email']);
                $former_invitation = $summit->getSubmissionInvitationByEmail($email);
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
     * @param Summit $summit
     * @param int $invitation_id
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function delete(Summit $summit, int $invitation_id): void
    {
        $this->tx_service->transaction(function () use ($summit, $invitation_id) {
            $invitation = $summit->getSubmissionInvitationById($invitation_id);
            if (is_null($invitation)) {
                throw new EntityNotFoundException("Invitation not found.");
            }

            $summit->removeSubmissionInvitation($invitation);
        });
    }

    /**
     * @param Summit $summit
     */
    public function deleteAll(Summit $summit): void
    {
        $this->tx_service->transaction(function () use ($summit) {
            $summit->clearSubmissionInvitations();
        });
    }

    /**
     * @param Summit $summit
     * @param array $payload
     * @return SummitSubmissionInvitation
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function add(Summit $summit, array $payload): SummitSubmissionInvitation
    {
        return $this->tx_service->transaction(function () use ($summit, $payload) {

            $email = trim($payload['email']);
            Log::debug(sprintf("SummitSubmissionInvitationService::add trying to add email %s", $email));

            $former_invitation = $summit->getSubmissionInvitationByEmail($email);
            if (!is_null($former_invitation)) {
                throw new ValidationException(sprintf("Email %s already has been invited for summit %s", $email, $summit->getId()));
            }

            $invitation = SummitSubmissionInvitationFactory::build($payload);

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
                $invitation = $this->setInvitationSpeaker($invitation, $email);
            } catch (\Exception $ex) {
                Log::warning($ex);
            }

            Log::debug(sprintf("SummitSubmissionInvitationService::add adding invitation for email %s to summit %s", $email, $summit->getName()));
            $summit->addSubmissionInvitation($invitation);

            return $invitation;
        });
    }

    /**
     * @param SummitSubmissionInvitation $invitation
     * @param string $email
     * @return SummitSubmissionInvitation
     * @throws \Exception
     */
    private function setInvitationSpeaker(SummitSubmissionInvitation $invitation, string $email): SummitSubmissionInvitation
    {
        return $this->tx_service->transaction(function () use ($invitation, $email) {
            try {
                $speaker = $this->speaker_repository->getByEmail($email);
                // create it
                if (is_null($speaker)) {

                    Log::debug(sprintf("SummitSubmissionInvitationService::setInvitationSpeaker - creating speaker for %s", $email));

                    $speaker = $this->speaker_service->addSpeaker([
                        'email' => $email,
                        'first_name' => $invitation->getFirstName(),
                        'last_name' => $invitation->getLastName(),
                    ]);
                }

                $invitation->setSpeaker($speaker);
            } catch (\Exception $ex) {
                Log::warning($ex);
            }
            return $invitation;
        });
    }

    /**
     * @param Summit $summit
     * @param int $invitation_id
     * @param array $payload
     * @return SummitSubmissionInvitation
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function update(Summit $summit, int $invitation_id, array $payload): SummitSubmissionInvitation
    {
        return $this->tx_service->transaction(function () use ($summit, $payload, $invitation_id) {
            $invitation = $summit->getSubmissionInvitationById($invitation_id);
            if (is_null($invitation))
                throw new EntityNotFoundException(sprintf("Invitation %s not found at Summit %s", $invitation_id, $summit->getId()));

            if (isset($payload['email'])) {
                $email = trim($payload['email']);
                $former_invitation = $summit->getSubmissionInvitationByEmail($email);
                if (!is_null($former_invitation) && $former_invitation->getId() !== $invitation_id) {
                    throw new ValidationException(sprintf("Email %s already has been invited for summit %s", $email, $summit->getId()));
                }
            }

            $invitation = SummitSubmissionInvitationFactory::populate($invitation, $payload);

            if (isset($payload['email'])) {
                $email = trim($payload['email']);
                try {
                    $invitation = $this->setInvitationSpeaker($invitation, $email);
                } catch (\Exception $ex) {
                    Log::warning($ex);
                }
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

            return $invitation;
        });
    }

    /**
     * @param Summit $summit
     * @param array $payload
     * @param mixed $filter
     */
    public function triggerSend(Summit $summit, array $payload, $filter = null): void
    {
        ProcessSubmissionsInvitationsJob::dispatch($summit, $payload, $filter);
    }

    use ParametrizedSendEmails;
    /**
     * @param int $summit_id
     * @param array $payload
     * @param Filter|null $filter
     * @throws \Exception
     */
    public function send(int $summit_id, array $payload, Filter $filter = null): void
    {
        $this->_sendEmails(
            $summit_id,
            $payload,
            "invitations",
            function ($summit, $paging_info, $filter, $resetPage) {

                if (!$filter->hasFilter("summit_id"))
                    $filter->addFilterCondition(FilterElement::makeEqual('summit_id', $summit->getId()));

                if ($filter->hasFilter("is_sent") && is_callable($resetPage)) {
                    // we need to reset the page bc the page processing will mark the current page as "sent"
                    // and adding an offset will move the cursor forward, leaving next round of not send out of the current process
                    $resetPage();
                }
                return $this->invitation_repository->getAllIdsByPage($paging_info, $filter);
            },
            function ($summit, $flow_event, $invitation_id, $test_email_recipient, $announcement_email_config, $filter) use ($payload) {
                try {
                    $this->tx_service->transaction(function () use (
                        $summit,
                        $flow_event,
                        $invitation_id,
                        $test_email_recipient,
                        $filter,
                        $payload
                    ) {
                        $res = $this->tx_service->transaction(function () use ($flow_event, $invitation_id, $payload) {

                            Log::debug(sprintf("SummitSubmissionInvitationService::send processing invitation id  %s", $invitation_id));

                            $invitation = $this->invitation_repository->getById(intval($invitation_id));
                            if (!$invitation instanceof SummitSubmissionInvitation) return null;

                            if(empty($invitation->getOtp())) {
                                $otp = null;
                                try {
                                    // generate inline OTP
                                    $otp = $this->passwordless_api->generateInlineOTP
                                    (
                                        $invitation->getEmail(),
                                        Config::get("cfp.client_id"),
                                        Config::get("cfp.scopes")
                                    );
                                } catch (\Exception $ex) {
                                    Log::error($ex);
                                    $otp = null;
                                }

                                if (is_null($otp)) {
                                    Log::warning(sprintf("SummitSubmissionInvitationService::send can not generate OTP for invitation %s", $invitation->getId()));
                                    return null;
                                }

                                Log::debug(sprintf("SummitSubmissionInvitationService::send got OTP %s for invitation %s", json_encode($otp), $invitation->getId()));
                                $invitation->setOtp($otp['value']);
                            }

                            $invitation->markAsSent();

                            if(isset($payload['selection_plan_id'])){
                                $selection_plan_id = intval($payload['selection_plan_id']);
                                $summit = $invitation->getSummit();
                                $email = $invitation->getEmail();
                                $selection_plan = $summit->getSelectionPlanById($selection_plan_id);
                                if(is_null($selection_plan)){
                                    Log::warning(sprintf("SummitSubmissionInvitationService::send selection plan %s does not exists on summit %s", $selection_plan_id, $summit->getId()));
                                    return null;
                                }
                                if(!$selection_plan->isAllowedMember($email)) {
                                    Log::debug(sprintf("SummitSubmissionInvitationService::send adding %s to selection plan %s", $email, $selection_plan_id));
                                    $selection_plan->addAllowedMember($email);
                                }
                            }

                            return $invitation;
                        });

                        // send email
                        if ($flow_event == InviteSubmissionEmail::EVENT_SLUG && !is_null($res))
                            InviteSubmissionEmail::dispatch($res, $payload);
                        if($flow_event == ReInviteSubmissionEmail::EVENT_SLUG && !is_null($res))
                            ReInviteSubmissionEmail::dispatch($res, $payload);
                    });
                } catch (\Exception $ex) {
                    Log::warning($ex);
                    EmailExcerpt::addErrorMessage($ex->getMessage());
                }
            },
            null,
            $filter
        );
    }
}