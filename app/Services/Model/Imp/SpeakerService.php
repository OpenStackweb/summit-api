<?php namespace services\model;
/**
 * Copyright 2017 OpenStack Foundation
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

use App\Jobs\Emails\PresentationSubmissions\SelectionProcess\PresentationSpeakerSelectionProcessExcerptEmail;
use App\Jobs\Emails\ProcessSpeakersEmailRequestJob;
use App\Jobs\Emails\Registration\PromoCodeEmailFactory;
use App\Jobs\Emails\PresentationSubmissions\SpeakerEditPermissionApprovedEmail;
use App\Jobs\Emails\PresentationSubmissions\SpeakerEditPermissionRejectedEmail;
use App\Jobs\Emails\PresentationSubmissions\SpeakerEditPermissionRequestedEmail;
use App\Models\Foundation\Main\CountryCodes;
use App\Models\Foundation\Main\Repositories\ILanguageRepository;
use App\Models\Foundation\Summit\Factories\PresentationSpeakerSummitAssistanceConfirmationRequestFactory;
use App\Models\Foundation\Summit\Factories\SpeakerEditPermissionRequestFactory;
use App\Models\Foundation\Summit\PromoCodes\PromoCodesConstants;
use App\Models\Foundation\Summit\Repositories\IPresentationSpeakerSummitAssistanceConfirmationRequestRepository;
use App\Models\Foundation\Summit\Repositories\ISpeakerActiveInvolvementRepository;
use App\Models\Foundation\Summit\Repositories\ISpeakerEditPermissionRequestRepository;
use App\Models\Foundation\Summit\Repositories\ISpeakerOrganizationalRoleRepository;
use App\Models\Foundation\Summit\Speakers\SpeakerEditPermissionRequest;
use App\Services\Model\AbstractService;
use App\Services\Model\IFolderService;
use App\Services\Model\Imp\Traits\ParametrizedSendEmails;
use App\Services\Model\Strategies\EmailActions\SpeakerActionsEmailStrategy;
use App\Services\Utils\Facades\EmailExcerpt;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use libs\utils\ITransactionService;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use models\main\File;
use models\main\IMemberRepository;
use models\main\Member;
use models\summit\ISpeakerRegistrationRequestRepository;
use models\summit\ISpeakerRepository;
use models\summit\ISpeakerSummitRegistrationPromoCodeRepository;
use models\summit\ISummitRepository;
use models\summit\PresentationSpeaker;
use models\summit\PresentationSpeakerSummitAssistanceConfirmationRequest;
use models\summit\SpeakerExpertise;
use models\summit\SpeakerOrganizationalRole;
use models\summit\SpeakerPresentationLink;
use models\summit\SpeakerRegistrationRequest;
use models\summit\SpeakerSummitRegistrationPromoCode;
use models\summit\SpeakerTravelPreference;
use models\summit\Summit;
use App\Http\Utils\IFileUploader;
use models\summit\SummitRegistrationPromoCode;
use utils\Filter;

/**
 * Class SpeakerService
 * @package services\model
 */
final class SpeakerService
    extends AbstractService
    implements ISpeakerService
{
    use ParametrizedSendEmails;

    /**
     * @var ISpeakerRepository
     */
    private $speaker_repository;

    /**
     * @var IMemberRepository
     */
    private $member_repository;

    /**
     * @var IFolderService
     */
    private $folder_service;

    /**
     * @var ISpeakerRegistrationRequestRepository
     */
    private $speaker_registration_request_repository;

    /**
     * @var ISpeakerSummitRegistrationPromoCodeRepository
     */
    private $registration_code_repository;

    /**
     * @var IPresentationSpeakerSummitAssistanceConfirmationRequestRepository
     */
    private $speakers_assistance_repository;

    /**
     * @var ILanguageRepository
     */
    private $language_repository;

    /**
     * @var ISpeakerOrganizationalRoleRepository
     */
    private $speaker_organizational_role_repository;

    /**
     * @var ISpeakerActiveInvolvementRepository
     */
    private $speaker_involvement_repository;

    /**
     * @var IFileUploader
     */
    private $file_uploader;

    /**
     * @var ISpeakerEditPermissionRequestRepository
     */
    private $speaker_edit_permisssion_repository;

    /**
     * @var ISummitRepository
     */
    private $summit_repository;

    /**
     * SpeakerService constructor.
     * @param ISpeakerRepository $speaker_repository
     * @param IMemberRepository $member_repository
     * @param ISpeakerRegistrationRequestRepository $speaker_registration_request_repository
     * @param ISpeakerSummitRegistrationPromoCodeRepository $registration_code_repository
     * @param IFolderService $folder_service
     * @param IPresentationSpeakerSummitAssistanceConfirmationRequestRepository $speakers_assistance_repository
     * @param ILanguageRepository $language_repository
     * @param ISpeakerOrganizationalRoleRepository $speaker_organizational_role_repository
     * @param ISpeakerActiveInvolvementRepository $speaker_involvement_repository
     * @param IFileUploader $file_uploader
     * @param ISpeakerEditPermissionRequestRepository $speaker_edit_permisssion_repository
     * @param ISummitRepository $summit_repository
     * @param ITransactionService $tx_service
     */
    public function __construct
    (
        ISpeakerRepository                                                $speaker_repository,
        IMemberRepository                                                 $member_repository,
        ISpeakerRegistrationRequestRepository                             $speaker_registration_request_repository,
        ISpeakerSummitRegistrationPromoCodeRepository                     $registration_code_repository,
        IFolderService                                                    $folder_service,
        IPresentationSpeakerSummitAssistanceConfirmationRequestRepository $speakers_assistance_repository,
        ILanguageRepository                                               $language_repository,
        ISpeakerOrganizationalRoleRepository                              $speaker_organizational_role_repository,
        ISpeakerActiveInvolvementRepository                               $speaker_involvement_repository,
        IFileUploader                                                     $file_uploader,
        ISpeakerEditPermissionRequestRepository                           $speaker_edit_permisssion_repository,
        ISummitRepository                                                 $summit_repository,
        ITransactionService                                               $tx_service
    )
    {
        parent::__construct($tx_service);
        $this->speaker_repository = $speaker_repository;
        $this->member_repository = $member_repository;
        $this->folder_service = $folder_service;
        $this->speaker_registration_request_repository = $speaker_registration_request_repository;
        $this->registration_code_repository = $registration_code_repository;
        $this->speakers_assistance_repository = $speakers_assistance_repository;
        $this->language_repository = $language_repository;
        $this->speaker_organizational_role_repository = $speaker_organizational_role_repository;
        $this->speaker_involvement_repository = $speaker_involvement_repository;
        $this->file_uploader = $file_uploader;
        $this->summit_repository = $summit_repository;
        $this->speaker_edit_permisssion_repository = $speaker_edit_permisssion_repository;
    }

    /**
     * @param array $data
     * @param null|Member $creator
     * @param bool $send_email
     * @return PresentationSpeaker
     * @throws ValidationException
     */
    public function addSpeaker(array $data, ?Member $creator = null, $send_email = true)
    {

        return $this->tx_service->transaction(function () use ($data, $creator, $send_email) {

            $member_id = intval($data['member_id'] ?? 0);
            $email = trim($data['email'] ?? '');

            Log::debug(sprintf("SpeakerService::addSpeaker: member id %s email %s", $member_id, $email));

            if (empty($email) && $member_id == 0)
                throw
                new ValidationException
                (trans("validation_errors.SpeakerService.addSpeaker.MissingMemberOrEmail"));

            $speaker = new PresentationSpeaker();
            $speaker->setCreatedFromApi(true);

            // check if we have a previous registration request and user it

            $formerRegistrationRequest = null;
            if (!empty($email)) {
                $formerRegistrationRequest = $this->speaker_registration_request_repository->getByEmail($email);
                if (!is_null($formerRegistrationRequest)) {
                    if ($formerRegistrationRequest->isConfirmed()) {
                        throw new ValidationException(sprintf("Speaker already exists and its confirmed."));
                    }
                    $speaker = $formerRegistrationRequest->getSpeaker();
                }
            }

            // if we pass the member , honor that and override email
            if ($member_id > 0) {
                $member = $this->member_repository->getById($member_id);
                if (is_null($member) || !$member instanceof Member)
                    throw new EntityNotFoundException(sprintf("member id %s does not exists!", $member_id));

                $existent_speaker = $this->speaker_repository->getByMember($member);
                if (!is_null($existent_speaker))
                    throw new ValidationException
                    (
                        trans("validation_errors.SpeakerService.addSpeaker.MemberAlreadyAssigned2Speaker",
                            [
                                'member_id' => $member_id,
                                'speaker_id' => $existent_speaker->getId()
                            ])
                    );

                $speaker->setMember($member);
            }

            // if we dont pass the member , try to get member by email
            if ($member_id == 0 && !empty($email)) {
                Log::debug(sprintf("SpeakerService::addSpeaker: member id is zero email is %s", $email));
                $member = $this->member_repository->getByEmail($email);
                if (!is_null($member)) {
                    Log::debug(sprintf("SpeakerService::addSpeaker: member %s found, setting it to speaker", $email));
                    $existent_speaker = $this->speaker_repository->getByMember($member);
                    if (!is_null($existent_speaker))
                        throw new ValidationException
                        (
                            trans("validation_errors.SpeakerService.addSpeaker.MemberAlreadyAssigned2Speaker",
                                [
                                    'member_id' => $member->getIdentifier(),
                                    'speaker_id' => $existent_speaker->getId()
                                ])

                        );
                    $speaker->setMember($member);
                }
                // if member does not exists and we dont have a former registration request
                if (is_null($member) && is_null($formerRegistrationRequest)) {
                    Log::debug(sprintf("SpeakerService::addSpeaker: member %s not found", $email));
                    $request = $this->registerSpeaker($speaker, $email);
                    if (!is_null($creator))
                        $request->setProposer($creator);
                }
            }

            $this->updateSpeakerMainData($speaker, $data);

            $this->speaker_repository->add($this->updateSpeakerRelations($speaker, $data));

            // only send the email if we dont have a former registration request
            //if(is_null($formerRegistrationRequest) && $send_email)
            //    SpeakerCreationEmail::dispatch($speaker);

            if (!is_null($formerRegistrationRequest)) {
                $formerRegistrationRequest->confirm();
            }

            if (!is_null($creator)) {
                // create edit permission for creator
                $request = SpeakerEditPermissionRequestFactory::build($speaker, $creator);
                $request->approve();
                $this->speaker_edit_permisssion_repository->add($request);
            }
            return $speaker;
        });
    }

    /**
     * @param Summit $summit
     * @param array $data
     * @return PresentationSpeaker
     * @throws ValidationException
     */
    public function addSpeakerBySummit(Summit $summit, array $data)
    {

        return $this->tx_service->transaction(function () use ($data, $summit) {

            $speaker = $this->addSpeaker($data);

            $speaker->addSummitAssistance(
                PresentationSpeakerSummitAssistanceConfirmationRequestFactory::build($summit, $speaker, $data)
            );

            $reg_code = isset($data['registration_code']) ? trim($data['registration_code']) : null;

            if (!empty($reg_code)) {
                $this->registerSummitPromoCodeByValue($speaker, $summit, $reg_code);
            }

            return $speaker;
        });
    }

    /**
     * @param array $data
     * @param PresentationSpeaker $speaker
     * @return PresentationSpeaker
     * @throws ValidationException
     */
    public function updateSpeaker(PresentationSpeaker $speaker, array $data)
    {
        return $this->tx_service->transaction(function () use ($speaker, $data) {
            $member_id = isset($data['member_id']) ? intval($data['member_id']) : null;

            if ($member_id > 0) {
                $member = $this->member_repository->getById($member_id);
                if (is_null($member))
                    throw new EntityNotFoundException;

                $existent_speaker = $this->speaker_repository->getByMember($member);
                if ($existent_speaker && $existent_speaker->getId() !== $speaker->getId())
                    throw new ValidationException
                    (
                        trans("validation_errors.SpeakerService.updateSpeaker.MemberAlreadyAssigned2Speaker",
                            [
                                'member_id' => $member_id,
                                'speaker_id' => $existent_speaker->getId()
                            ])
                    );

                $speaker->setMember($member);
            }

            return $this->updateSpeakerRelations($this->updateSpeakerMainData($speaker, $data), $data);

        });
    }

    /**
     * @param Summit $summit
     * @param array $data
     * @param PresentationSpeaker $speaker
     * @return PresentationSpeaker
     * @throws ValidationException
     * @throws EntityNotFoundException
     */
    public function updateSpeakerBySummit(Summit $summit, PresentationSpeaker $speaker, array $data)
    {
        return $this->tx_service->transaction(function () use ($summit, $speaker, $data) {

            $speaker = $this->updateSpeaker($speaker, $data);

            // get summit assistance
            $summit_assistance = $speaker->getAssistanceFor($summit);
            // if does not exists create it
            if (is_null($summit_assistance)) {
                $summit_assistance = $speaker->buildAssistanceFor($summit);
                $speaker->addSummitAssistance($summit_assistance);
            }

            PresentationSpeakerSummitAssistanceConfirmationRequestFactory::populate($summit_assistance, $data);

            $reg_code = isset($data['registration_code']) ? trim($data['registration_code']) : null;
            if (!empty($reg_code)) {
                $this->registerSummitPromoCodeByValue($speaker, $summit, $reg_code);
            }

            return $speaker;
        });
    }

    /**
     * @param PresentationSpeaker $speaker
     * @param string $email
     * @return SpeakerRegistrationRequest
     * @throws ValidationException
     */
    private function registerSpeaker(PresentationSpeaker $speaker, $email)
    {

        if ($this->speaker_registration_request_repository->existByEmail($email))
            throw new ValidationException(sprintf("email %s already has a Speaker Registration Request", $email));

        $registration_request = new SpeakerRegistrationRequest();
        $registration_request->setEmail($email);

        do {
            $registration_request->generateConfirmationToken();
        } while ($this->speaker_registration_request_repository->existByHash($registration_request->getConfirmationHash()));

        $speaker->setRegistrationRequest($registration_request);
        return $registration_request;
    }

    /**
     * @param PresentationSpeaker $speaker
     * @param Summit $summit
     * @param string $reg_code
     * @return SpeakerSummitRegistrationPromoCode
     * @throws ValidationException
     */
    public function registerSummitPromoCodeByValue(PresentationSpeaker $speaker, Summit $summit, $reg_code)
    {

        return $this->tx_service->transaction(function () use ($speaker, $summit, $reg_code) {
            // check if our speaker already has an assigned code for this summit ...
            $existent_code = $this->registration_code_repository->getBySpeakerAndSummit($speaker, $summit);

            // we are trying to update the promo code with another one ....
            if (!is_null($existent_code) && $reg_code !== $existent_code->getCode() && $existent_code->isRedeemed()) {
                throw new ValidationException(sprintf(
                    'speaker has been already assigned to another registration code (%s) already redeemed!', $existent_code->getCode()
                ));
            }

            if (!is_null($existent_code) && $reg_code == $existent_code->getCode()) return $existent_code;

            // check if reg code is assigned already to another speaker ...
            if ($assigned_code = $this->registration_code_repository->getAssignedCode($reg_code, $summit)) {

                if ($assigned_code->getSpeaker()->getId() != $speaker->getId())
                    throw new ValidationException(sprintf(
                        'there is another speaker with that code for this summit ( speaker id %s )', $assigned_code->getSpeaker()->getId()
                    ));
            }
            // check is not assigned already
            $new_code = $this->registration_code_repository->getNotAssignedCode($reg_code, $summit);

            if (is_null($new_code)) {
                // create it
                $new_code = new SpeakerSummitRegistrationPromoCode();
                $new_code->setSummit($summit);
                $new_code->setCode($reg_code);
                $new_code->setSourceAdmin();
                $new_code->setSpeaker($speaker);

                PromoCodeEmailFactory::send($new_code);
            }

            $speaker->addPromoCode($new_code);
            if (!is_null($existent_code)) {
                $speaker->removePromoCode($existent_code);
            }
            return $new_code;
        });

    }

    /**
     * @param PresentationSpeaker $speaker
     * @param array $data
     * @return PresentationSpeaker
     */
    private function updateSpeakerMainData(PresentationSpeaker $speaker, array $data)
    {
        if (isset($data['title']))
            $speaker->setTitle(trim($data['title']));

        if (isset($data['bio']))
            $speaker->setBio(trim($data['bio']));

        if (isset($data['first_name']))
            $speaker->setFirstName(trim($data['first_name']));

        if (isset($data['last_name']))
            $speaker->setLastName(trim($data['last_name']));

        if (isset($data['irc']))
            $speaker->setIrcHandle(trim($data['irc']));

        if (isset($data['twitter']))
            $speaker->setTwitterName(trim($data['twitter']));

        if (isset($data['notes']))
            $speaker->setNotes(trim($data['notes']));

        if (isset($data['available_for_bureau']))
            $speaker->setAvailableForBureau(boolval($data['available_for_bureau']));

        if (isset($data['funded_travel']))
            $speaker->setFundedTravel(boolval($data['funded_travel']));

        if (isset($data['willing_to_travel']))
            $speaker->setWillingToTravel(boolval($data['willing_to_travel']));

        if (isset($data['willing_to_present_video']))
            $speaker->setWillingToPresentVideo(boolval($data['willing_to_present_video']));

        if (isset($data['org_has_cloud']))
            $speaker->setOrgHasCloud(boolval($data['org_has_cloud']));

        if (isset($data['country']))
            $speaker->setCountry(trim($data['country']));

        if (isset($data['company']))
            $speaker->setCompany(trim($data['company']));

        if (isset($data['phone_number']))
            $speaker->setPhoneNumber(trim($data['phone_number']));

        return $speaker;
    }

    /**
     * @param PresentationSpeaker $speaker
     * @param array $data
     * @return PresentationSpeaker
     */
    private function updateSpeakerRelations(PresentationSpeaker $speaker, array $data)
    {

        // other_presentation_links

        if (isset($data['other_presentation_links']) && is_array($data['other_presentation_links'])) {
            $speaker->clearOtherPresentationLinks();
            foreach ($data['other_presentation_links'] as $link) {
                $speaker->addOtherPresentationLink(new SpeakerPresentationLink(trim($link['link']), trim($link['title'])));
            }
        }
        // languages

        if (isset($data['languages']) && is_array($data['languages'])) {
            $speaker->clearLanguages();
            foreach ($data['languages'] as $lang_id) {
                $language = $this->language_repository->getById(intval($lang_id));
                if (is_null($language))
                    throw new ValidationException(
                        trans("validation_errors.SpeakerService.updateSpeakerRelations.InvalidLanguage", [
                            'lang_id' => $lang_id
                        ])
                    );
                $speaker->addLanguage($language);
            }
        }

        // travel_preferences

        if (isset($data['travel_preferences']) && is_array($data['travel_preferences'])) {
            $speaker->clearTravelPreferences();
            foreach ($data['travel_preferences'] as $country) {
                if (!isset(CountryCodes::$iso_3166_countryCodes[$country])) {
                    throw new ValidationException(
                        trans("validation_errors.SpeakerService.updateSpeakerRelations.InvalidCountryCode", [
                            'country' => $country
                        ]));
                }
                $speaker->addTravelPreference(new SpeakerTravelPreference($country));
            }
        }
        // areas_of_expertise

        if (isset($data['areas_of_expertise']) && is_array($data['areas_of_expertise'])) {
            $speaker->clearAreasOfExpertise();
            foreach ($data['areas_of_expertise'] as $expertise) {
                $speaker->addAreaOfExpertise(new SpeakerExpertise(trim($expertise)));
            }
        }

        // organizational_roles

        if (isset($data['organizational_roles']) && is_array($data['organizational_roles'])) {
            $speaker->clearOrganizationalRoles();
            foreach ($data['organizational_roles'] as $org_role_id) {
                $role = $this->speaker_organizational_role_repository->getById(intval($org_role_id));
                if (is_null($role)) {
                    throw new ValidationException(
                        trans("validation_errors.SpeakerService.updateSpeakerRelations.InvalidOrganizationRole", [
                            'role' => $role
                        ]));
                }
                $speaker->addOrganizationalRole($role);
            }

            // other
            if (isset($data['other_organizational_rol'])) {
                $role = $this->speaker_organizational_role_repository->getByRole(trim($data['other_organizational_rol']));
                if (is_null($role)) {
                    // create it
                    $role = new SpeakerOrganizationalRole(trim($data['other_organizational_rol']));
                    $this->speaker_organizational_role_repository->add($role);
                }
                $speaker->addOrganizationalRole($role);
            }
        }

        // active_involvements

        if (isset($data['active_involvements']) && is_array($data['active_involvements'])) {
            $speaker->clearActiveInvolvements();
            foreach ($data['active_involvements'] as $involvement_id) {
                $involvement = $this->speaker_involvement_repository->getById(intval($involvement_id));
                if (is_null($involvement)) {
                    throw new ValidationException(
                        trans("validation_errors.SpeakerService.updateSpeakerRelations.InvalidActiveInvolvement", [
                            'involvement_id' => $involvement_id
                        ]));
                }
                $speaker->addActiveInvolvement($involvement);
            }
        }

        return $speaker;
    }

    /**
     * @param Summit $summit
     * @param PresentationSpeaker $speaker
     * @param Filter|null $filter
     * @return SummitRegistrationPromoCode|null
     * @throws \Exception
     */
    private function getPromoCode(Summit $summit, PresentationSpeaker $speaker, ?Filter $filter = null): ?SummitRegistrationPromoCode
    {
        return $this->tx_service->transaction(function() use($speaker, $summit, $filter) {

            $promo_code = $speaker->getPromoCodeFor($summit);

            if (is_null($promo_code)) {
                $promo_code = $speaker->getDiscountCodeFor($summit);
            }

            if (is_null($promo_code)) {
                // try to get a new one

                $has_accepted =
                    $speaker->hasAcceptedPresentations($summit, PresentationSpeaker::RoleModerator, true, $summit->getExcludedCategoriesForAcceptedPresentations(), $filter) ||
                    $speaker->hasAcceptedPresentations($summit, PresentationSpeaker::RoleSpeaker, true, $summit->getExcludedCategoriesForAcceptedPresentations(), $filter);

                $has_alternate =
                    $speaker->hasAlternatePresentations($summit, PresentationSpeaker::RoleModerator, true, $summit->getExcludedCategoriesForAlternatePresentations(), $filter) ||
                    $speaker->hasAlternatePresentations($summit, PresentationSpeaker::RoleSpeaker, true, $summit->getExcludedCategoriesForAlternatePresentations(), $filter);

                if ($has_accepted) //get approved code
                {
                    $promo_code = $this->registration_code_repository->getNextAvailableByType
                    (
                        $summit,
                        PromoCodesConstants::SpeakerSummitRegistrationPromoCodeTypeAccepted
                    );
                    /*
                    if (is_null($promo_code))
                        throw new ValidationException
                        (
                            trans
                            (
                                'validation_errors.send_speaker_summit_assistance_announcement_mail_run_out_promo_code',
                                [
                                    'summit_id' => $summit->getId(),
                                    'speaker_id' => $speaker->getId(),
                                    'speaker_email' => $speaker->getEmail(),
                                    'type' => PromoCodesConstants::SpeakerSummitRegistrationPromoCodeTypeAccepted
                                ]
                            )
                        );
                    */
                    if (!is_null($promo_code))
                        $speaker->addPromoCode($promo_code);
                } else if ($has_alternate) // get alternate code
                {
                    $promo_code = $this->registration_code_repository->getNextAvailableByType
                    (
                        $summit,
                        PromoCodesConstants::SpeakerSummitRegistrationPromoCodeTypeAlternate
                    );
                    /*
                    if (is_null($promo_code))
                        throw new ValidationException
                        (
                            trans
                            (
                                'validation_errors.send_speaker_summit_assistance_announcement_mail_run_out_promo_code',
                                [
                                    'summit_id' => $summit->getId(),
                                    'speaker_id' => $speaker->getId(),
                                    'speaker_email' => $speaker->getEmail(),
                                    'type' => PromoCodesConstants::SpeakerSummitRegistrationPromoCodeTypeAccepted
                                ]
                            )

                        );
                    */
                    if (!is_null($promo_code))
                        $speaker->addPromoCode($promo_code);
                }
            }

            return $promo_code;
        });
    }

    /**
     * @param PresentationSpeaker $speaker_from
     * @param PresentationSpeaker $speaker_to
     * @param array $data
     * @return void
     */
    public function merge(PresentationSpeaker $speaker_from, PresentationSpeaker $speaker_to, array $data)
    {
        return $this->tx_service->transaction(function () use ($speaker_from, $speaker_to, $data) {

            if ($speaker_from->getIdentifier() == $speaker_to->getIdentifier())
                throw new ValidationException("You can not merge the same speaker!");
            // bio
            if (!isset($data['bio'])) throw new ValidationException("bio field is required");
            $speaker_id = intval($data['bio']);
            $speaker_to->setBio($speaker_id == $speaker_from->getId() ? $speaker_from->getBio() : $speaker_to->getBio());

            // first_name
            if (!isset($data['first_name'])) throw new ValidationException("first_name field is required");
            $speaker_id = intval($data['first_name']);
            $speaker_to->setFirstName($speaker_id == $speaker_from->getId() ? $speaker_from->getFirstName() : $speaker_to->getFirstName());

            // last_name
            if (!isset($data['last_name'])) throw new ValidationException("last_name field is required");
            $speaker_id = intval($data['last_name']);
            $speaker_to->setLastName($speaker_id == $speaker_from->getId() ? $speaker_from->getLastName() : $speaker_to->getLastName());

            // title
            if (!isset($data['title'])) throw new ValidationException("title field is required");
            $speaker_id = intval($data['title']);
            $speaker_to->setTitle($speaker_id == $speaker_from->getId() ? $speaker_from->getTitle() : $speaker_to->getTitle());

            // irc
            if (!isset($data['irc'])) throw new ValidationException("irc field is required");
            $speaker_id = intval($data['irc']);
            $speaker_to->setIrcHandle($speaker_id == $speaker_from->getId() ? $speaker_from->getIrcHandle() : $speaker_to->getIrcHandle());

            // twitter
            if (!isset($data['twitter'])) throw new ValidationException("twitter field is required");
            $speaker_id = intval($data['twitter']);
            $speaker_to->setTwitterName($speaker_id == $speaker_from->getId() ? $speaker_from->getTwitterName() : $speaker_to->getTwitterName());

            // pic
            try {
                if (!isset($data['pic'])) throw new ValidationException("pic field is required");
                $speaker_id = intval($data['pic']);
                $photo = $speaker_id == $speaker_from->getId() ? $speaker_from->getPhoto() : $speaker_to->getPhoto();
                if (!is_null($photo))
                    $speaker_to->setPhoto($photo);
            } catch (\Exception $ex) {

            }
            // registration_request
            try {
                if (!isset($data['registration_request'])) throw new ValidationException("registration_request field is required");
                $speaker_id = intval($data['registration_request']);
                $registration_request = $speaker_id == $speaker_from->getId() ? $speaker_from->getRegistrationRequest() : $speaker_to->getRegistrationRequest();
                if (!is_null($registration_request))
                    $speaker_to->setRegistrationRequest($registration_request);
            } catch (\Exception $ex) {

            }
            // member
            try {
                if (!isset($data['member'])) throw new ValidationException("member field is required");
                $speaker_id = intval($data['member']);
                $member = $speaker_id == $speaker_from->getId() ? $speaker_from->getMember() : $speaker_to->getMember();
                if (!is_null($member))
                    $speaker_to->setMember($member);
            } catch (\Exception $ex) {

            }
            // presentations

            foreach ($speaker_from->getAllPresentations(false) as $presentation) {
                $speaker_to->addPresentation($presentation);
            }

            foreach ($speaker_from->getAllModeratedPresentations(false) as $presentation) {
                $speaker_to->addModeratedPresentation($presentation);
            }

            // languages

            foreach ($speaker_from->getLanguages() as $language) {
                $speaker_to->addLanguage($language);
            }

            // promo codes

            foreach ($speaker_from->getPromoCodes() as $code) {
                $speaker_to->addPromoCode($code);
            }

            // summit assistances

            foreach ($speaker_from->getSummitAssistances() as $assistance) {
                $speaker_to->addSummitAssistance($assistance);
            }

            // presentation links

            foreach ($speaker_from->getOtherPresentationLinks() as $link) {
                $speaker_to->addOtherPresentationLink($link);
            }

            // travel preferences

            foreach ($speaker_from->getTravelPreferences() as $travel_preference) {
                $speaker_to->addTravelPreference($travel_preference);
            }

            // areas of expertise

            foreach ($speaker_from->getAreasOfExpertise() as $areas_of_expertise) {
                $speaker_to->addAreaOfExpertise($areas_of_expertise);
            }

            // roles

            foreach ($speaker_from->getOrganizationalRoles() as $role) {
                $speaker_to->addOrganizationalRole($role);
            }
            $speaker_from->clearOrganizationalRoles();

            // involvements

            foreach ($speaker_from->getActiveInvolvements() as $involvement) {
                $speaker_to->addActiveInvolvement($involvement);
            }

            $speaker_from->clearActiveInvolvements();

            $this->speaker_repository->delete($speaker_from);
        });
    }

    /**
     * @param int $speaker_id
     * @return void
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function deleteSpeaker($speaker_id)
    {
        return $this->tx_service->transaction(function () use ($speaker_id) {
            $speaker = $this->speaker_repository->getById($speaker_id);
            if (is_null($speaker))
                throw new EntityNotFoundException;

            $this->speaker_repository->delete($speaker);
        });
    }

    /**
     * @param Summit $summit
     * @param array $data
     * @return PresentationSpeakerSummitAssistanceConfirmationRequest
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function addSpeakerAssistance(Summit $summit, array $data)
    {
        return $this->tx_service->transaction(function () use ($data, $summit) {

            $speaker_id = intval($data['speaker_id']);
            $speaker = $this->speaker_repository->getById($speaker_id);

            if (is_null($speaker))
                throw new EntityNotFoundException(trans('not_found_errors.add_speaker_assistance_speaker_not_found', ['speaker_id' => $speaker_id]));

            if (!$speaker->isSpeakerOfSummit($summit)) {
                throw new ValidationException(trans('validation_errors.add_speaker_assistance_speaker_is_not_on_summit',
                    [
                        'speaker_id' => $speaker_id,
                        'summit_id' => $summit->getId()
                    ]
                ));
            }

            if ($speaker->hasAssistanceFor($summit))
                throw new ValidationException(trans('validation_errors.add_speaker_assistance_speaker_already_has_assistance',
                    [
                        'speaker_id' => $speaker_id,
                        'summit_id' => $summit->getId()
                    ]
                ));

            $assistance = PresentationSpeakerSummitAssistanceConfirmationRequestFactory::build
            (
                $summit,
                $speaker,
                $data
            );

            $speaker->addSummitAssistance($assistance);

            return $assistance;
        });
    }

    /**
     * @param Summit $summit
     * @param int $assistance_id
     * @param array $data
     * @return PresentationSpeakerSummitAssistanceConfirmationRequest
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function updateSpeakerAssistance(Summit $summit, $assistance_id, array $data)
    {
        return $this->tx_service->transaction(function () use ($summit, $assistance_id, $data) {
            $assistance = $this->speakers_assistance_repository->getById($assistance_id);
            if (is_null($assistance))
                throw new EntityNotFoundException;

            if ($assistance->getSummitId() != $summit->getId()) {
                throw new ValidationException
                (
                    trans
                    (
                        'validation_errors.speaker_assistance_does_not_belongs_to_summit',
                        [
                            'assistance_id' => $assistance_id,
                            'summit_id' => $summit->getId()
                        ]
                    )
                );
            }

            return PresentationSpeakerSummitAssistanceConfirmationRequestFactory::populate
            (
                $assistance,
                $data
            );

        });
    }

    /**
     * @param Summit $summit
     * @param int $assistance_id
     * @return void
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function deleteSpeakerAssistance(Summit $summit, $assistance_id)
    {
        return $this->tx_service->transaction(function () use ($summit, $assistance_id) {

            $assistance = $this->speakers_assistance_repository->getById($assistance_id);

            if (is_null($assistance))
                throw new EntityNotFoundException;

            if ($assistance->getSummitId() != $summit->getId()) {
                throw new ValidationException
                (
                    trans
                    (
                        'validation_errors.speaker_assistance_does_not_belongs_to_summit',
                        [
                            'assistance_id' => $assistance_id,
                            'summit_id' => $summit->getId()
                        ]
                    )
                );
            }

            if ($assistance->isConfirmed())
                throw new ValidationException
                (
                    trans
                    (
                        'validation_errors.speaker_assistance_delete_already_confirmed',
                        [
                            'assistance_id' => $assistance_id,
                            'speaker_id' => $assistance->getSpeakerId()
                        ]
                    )
                );

            $this->speakers_assistance_repository->delete($assistance);
        });
    }

    /**
     * @param Summit $summit
     * @param PresentationSpeaker $speake
     * @param Filter|null $filter
     * @return PresentationSpeakerSummitAssistanceConfirmationRequest|null
     * @throws \Exception
     */
    private function generateSpeakerAssistance(Summit $summit, PresentationSpeaker $speaker, ?Filter $filter): ?PresentationSpeakerSummitAssistanceConfirmationRequest
    {
        return $this->tx_service->transaction(function () use ($summit, $speaker, $filter) {


            $has_accepted_presentations =
                $speaker->hasAcceptedPresentations(
                    $summit, PresentationSpeaker::RoleModerator, true,
                    $summit->getExcludedCategoriesForAcceptedPresentations(), $filter
                ) ||
                $speaker->hasAcceptedPresentations(
                    $summit, PresentationSpeaker::RoleSpeaker, true,
                    $summit->getExcludedCategoriesForAcceptedPresentations(), $filter
                );

            $has_alternate_presentations =
                $speaker->hasAlternatePresentations(
                    $summit, PresentationSpeaker::RoleModerator, true,
                    $summit->getExcludedCategoriesForAlternatePresentations(), $filter
                ) ||
                $speaker->hasAlternatePresentations(
                    $summit, PresentationSpeaker::RoleSpeaker, true,
                    $summit->getExcludedCategoriesForAlternatePresentations(), $filter
                );

            Log::debug
            (
                sprintf
                (
                    "SpeakerService::generateSpeakerAssistance speaker %s (%s) accepted %b alternate %b"
                    , $speaker->getEmail()
                    , $speaker->getId()
                    , $has_accepted_presentations
                    , $has_alternate_presentations
                )
            );

            if (!$has_accepted_presentations && !$has_alternate_presentations) {
                Log::debug
                (
                    sprintf
                    (
                        "SpeakerService::generateSpeakerAssistance speaker %s (%s) has not accepted neither alternate presentations for summit %s."
                        , $speaker->getEmail()
                        , $speaker->getId()
                        , $summit->getId()
                    )
                );
                return null;
            }

            try {
                Log::debug
                (
                    sprintf
                    (
                        "SpeakerService::generateSpeakerAssistance trying to get speaker assistance for speaker %s summit %s.",
                        $speaker->getId(), $summit->getId()
                    )
                );

                $assistance = $this->speakers_assistance_repository->getBySpeaker($speaker, $summit);

                if (is_null($assistance)) {
                    Log::debug
                    (
                        sprintf
                        (
                            "SpeakerService::generateSpeakerAssistance speaker assistance for speaker %s summit %s does not exists. creating one ...",
                            $speaker->getId(), $summit->getId()
                        )
                    );
                    $assistance = new PresentationSpeakerSummitAssistanceConfirmationRequest();
                    $speaker->addSummitAssistance($assistance);
                    $summit->addSpeakerAssistance($assistance);
                }

                do {
                    $assistance->generateConfirmationToken();
                } while ($this->speakers_assistance_repository->existByHash($assistance));


                return $assistance;
            } catch (\Exception $ex) {
                Log::warning($ex);
                return null;
            }
        });
    }

    /**
     * @param int $requested_by_id
     * @param int $speaker_id
     * @return SpeakerEditPermissionRequest
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function requestSpeakerEditPermission(int $requested_by_id, int $speaker_id): SpeakerEditPermissionRequest
    {
        return $this->tx_service->transaction(function () use ($requested_by_id, $speaker_id) {

            $requestor = $this->member_repository->getById($requested_by_id);
            if (is_null($requestor))
                throw new EntityNotFoundException();

            $speaker = $this->speaker_repository->getById($speaker_id);
            if (is_null($speaker))
                throw new EntityNotFoundException();

            $request = $this->speaker_edit_permisssion_repository->getBySpeakerAndRequestor($speaker, $requestor);
            if (!is_null($request) && $request->isActionTaken())
                throw new ValidationException("there is another permission edit request already redeem!");

            // build request with factory
            $request = SpeakerEditPermissionRequestFactory::build($speaker, $requestor);
            $token = $request->generateConfirmationToken();
            SpeakerEditPermissionRequestedEmail::dispatch($request, $token);
            $this->speaker_edit_permisssion_repository->add($request);

            return $request;
        });
    }

    /**
     * @param int $requested_by_id
     * @param int $speaker_id
     * @return SpeakerEditPermissionRequest
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function getSpeakerEditPermission(int $requested_by_id, int $speaker_id): SpeakerEditPermissionRequest
    {
        return $this->tx_service->transaction(function () use ($requested_by_id, $speaker_id) {

            $requestor = $this->member_repository->getById($requested_by_id);
            if (is_null($requestor))
                throw new EntityNotFoundException();

            $speaker = $this->speaker_repository->getById($speaker_id);
            if (is_null($speaker))
                throw new EntityNotFoundException();

            $request = $this->speaker_edit_permisssion_repository->getBySpeakerAndRequestor($speaker, $requestor);

            if (is_null($request) && $speaker->canBeEditedBy($requestor)) {
                $request = SpeakerEditPermissionRequestFactory::build($speaker, $requestor);
                $request->approve();
                $this->speaker_edit_permisssion_repository->add($request);
                return $request;
            }

            if (is_null($request))
                throw new EntityNotFoundException();

            return $request;
        });
    }

    /**
     * @param string $token
     * @param int $speaker_id
     * @return SpeakerEditPermissionRequest
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function approveSpeakerEditPermission(string $token, int $speaker_id): SpeakerEditPermissionRequest
    {
        return $this->tx_service->transaction(function () use ($token, $speaker_id) {
            $request = $this->speaker_edit_permisssion_repository->getByToken($token);
            if (is_null($request))
                throw new EntityNotFoundException();
            if ($request->isApproved())
                throw new ValidationException();
            $request->approve();
            SpeakerEditPermissionApprovedEmail::dispatch($request);
            return $request;
        });
    }

    /**
     * @param string $token
     * @param int $speaker_id
     * @return SpeakerEditPermissionRequest
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function rejectSpeakerEditPermission(string $token, int $speaker_id): SpeakerEditPermissionRequest
    {
        return $this->tx_service->transaction(function () use ($token, $speaker_id) {
            $request = $this->speaker_edit_permisssion_repository->getByToken($token);
            if (is_null($request))
                throw new EntityNotFoundException();
            if ($request->isActionTaken())
                throw new ValidationException();
            $request->reject();
            SpeakerEditPermissionRejectedEmail::dispatch($request);
            return $request;
        });
    }

    /**
     * @param int $speaker_id
     * @param UploadedFile $file
     * @param int $max_file_size
     * @return File
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function addSpeakerPhoto($speaker_id, UploadedFile $file, $max_file_size = 10485760)
    {
        return $this->tx_service->transaction(function () use ($speaker_id, $file, $max_file_size) {

            $allowed_extensions = ['png', 'jpg', 'jpeg', 'gif', 'pdf'];

            $speaker = $this->speaker_repository->getById($speaker_id);

            if (is_null($speaker) || !$speaker instanceof PresentationSpeaker) {
                throw new EntityNotFoundException('speaker not found!');
            }

            if (!in_array($file->extension(), $allowed_extensions)) {
                throw new ValidationException("file does not has a valid extension ('png','jpg','jpeg','gif','pdf').");
            }

            if ($file->getSize() > $max_file_size) {
                throw new ValidationException(sprintf("file exceeds max_file_size (%s MB).", ($max_file_size / 1024) / 1024));
            }

            $photo = $this->file_uploader->build($file, 'profile-images', true);
            $speaker->setPhoto($photo);

            return $photo;
        });
    }


    /**
     * @inheritDoc
     */
    public function deleteSpeakerPhoto($speaker_id): void
    {
        $this->tx_service->transaction(function () use ($speaker_id) {

            $speaker = $this->speaker_repository->getById($speaker_id);

            if (is_null($speaker) || !$speaker instanceof PresentationSpeaker) {
                throw new EntityNotFoundException('speaker not found!');
            }

            $speaker->clearPhoto();

        });
    }

    /**
     * @inheritDoc
     */
    public function addSpeakerBigPhoto($speaker_id, UploadedFile $file, $max_file_size = 10485760)
    {
        return $this->tx_service->transaction(function () use ($speaker_id, $file, $max_file_size) {

            $allowed_extensions = ['png', 'jpg', 'jpeg', 'gif', 'pdf'];

            $speaker = $this->speaker_repository->getById($speaker_id);

            if (is_null($speaker) || !$speaker instanceof PresentationSpeaker) {
                throw new EntityNotFoundException('speaker not found!');
            }

            if (!in_array($file->extension(), $allowed_extensions)) {
                throw new ValidationException("file does not has a valid extension ('png','jpg','jpeg','gif','pdf').");
            }

            if ($file->getSize() > $max_file_size) {
                throw new ValidationException(sprintf("file exceeds max_file_size (%s MB).", ($max_file_size / 1024) / 1024));
            }

            $photo = $this->file_uploader->build($file, 'profile-images', true);
            $speaker->setBigPhoto($photo);

            return $photo;
        });
    }

    /**
     * @inheritDoc
     */
    public function deleteSpeakerBigPhoto($speaker_id): void
    {
        $this->tx_service->transaction(function () use ($speaker_id) {


            $speaker = $this->speaker_repository->getById($speaker_id);

            if (is_null($speaker) || !$speaker instanceof PresentationSpeaker) {
                throw new EntityNotFoundException('speaker not found!');
            }

            $speaker->clearBigPhoto();
        });
    }

    /**
     * @inheritDoc
     */
    public function triggerSendEmails(Summit $summit, array $payload, $filter = null): void
    {
        ProcessSpeakersEmailRequestJob::dispatch($summit->getId(), $payload, $filter);
    }

    /**
     * @param int $summit_id
     * @param array $payload
     * @param Filter|null $filter
     * @throws ValidationException
     */
    public function sendEmails(int $summit_id, array $payload, Filter $filter = null): void
    {
        $this->_sendEmails(
            $summit_id,
            $payload,
            "speaker",
            function($summit, $paging_info, $filter) {
                return $this->speaker_repository->getSpeakersIdsBySummit($summit, $paging_info, $filter);
            },
            function($summit, $flow_event, $speaker_id, $test_email_recipient, $speaker_announcement_email_config, $filter) {
                try {
                    $this->tx_service->transaction(function () use
                    (
                        $summit,
                        $flow_event,
                        $speaker_id,
                        $test_email_recipient,
                        $speaker_announcement_email_config,
                        $filter
                    ) {
                        $email_strategy = new SpeakerActionsEmailStrategy($summit, $flow_event);

                        Log::debug(sprintf("SpeakerService::send processing speaker id %s", $speaker_id));

                        $speaker = $this->speaker_repository->getByIdExclusiveLock(intval($speaker_id));

                        if (!$speaker instanceof PresentationSpeaker) {
                            throw new EntityNotFoundException('speaker not found!');
                        }

                        // try to get a promo code
                        $promo_code = $this->getPromoCode($summit, $speaker, $filter);

                        // try to get a speaker assistance
                        $assistance = $this->generateSpeakerAssistance($summit, $speaker, $filter);

                        $email_strategy->process
                        (
                            $speaker,
                            $test_email_recipient,
                            $speaker_announcement_email_config,
                            $filter,
                            $promo_code,
                            $assistance
                        );
                    });
                } catch (\Exception $ex) {
                    Log::warning($ex);
                    EmailExcerpt::addErrorMessage($ex->getMessage());
                }
            },
            function($summit, $outcome_email_recipient, $report){
                PresentationSpeakerSelectionProcessExcerptEmail::dispatch($summit,$outcome_email_recipient, $report );
            },
            $filter
        );
    }

    /**
     * @param Member $member
     * @return PresentationSpeaker|null
     */
    public function getSpeakerByMember(Member $member): ?PresentationSpeaker
    {
        return $this->tx_service->transaction(function() use($member){
            Log::debug
            (
                sprintf
                (
                    "SpeakerService::getSpeakerByMember member %s (%s)",
                    $member->getEmail(),
                    $member->getId()
                )
            );

            $speaker = $this->speaker_repository->getByMember($member);
            if (is_null($speaker)){
                Log::debug
                (
                    sprintf
                    (
                        "SpeakerService::getSpeakerByMember member %s (%s) speaker not found, trying to get reg request...",
                        $member->getEmail(),
                        $member->getId()
                    )
                );

                $request = $this->speaker_registration_request_repository->getByEmail($member->getEmail());
                if(!is_null($request)){
                    Log::debug
                    (
                        sprintf
                        (
                            "SpeakerService::getSpeakerByMember member %s (%s) got former registration request.",
                            $member->getEmail(),
                            $member->getId()
                        )
                    );

                    $speaker = $request->getSpeaker();
                    $speaker->setMember($member);
                    $request->confirm();
                }
            }
            return $speaker;
        });
    }
}