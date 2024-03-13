<?php namespace App\Services\Model;
/**
 * Copyright 2018 OpenStack Foundation
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

use App\Jobs\Emails\Registration\PromoCodes\ProcessSponsorPromoCodesJob;
use App\Jobs\Emails\Registration\PromoCodes\PromoCodeEmailFactory;
use App\Jobs\Emails\Registration\PromoCodes\SponsorPromoCodeEmail;
use App\Jobs\ReApplyPromoCodeRetroActively;
use App\Models\Foundation\Summit\Factories\SummitPromoCodeFactory;
use App\Models\Foundation\Summit\Factories\SummitRegistrationDiscountCodeTicketTypeRuleFactory;
use App\Services\Model\Imp\Traits\ParametrizedSendEmails;
use App\Services\Model\Strategies\PromoCodes\PromoCodeValidationStrategyFactory;
use App\Services\Utils\CSVReader;
use App\Services\Utils\ILockManagerService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use libs\utils\ITransactionService;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use models\main\ICompanyRepository;
use models\main\IMemberRepository;
use models\main\ITagRepository;
use models\main\Member;
use models\main\Tag;
use models\summit\IOwnablePromoCode;
use models\summit\ISpeakerRepository;
use models\summit\ISummitAttendeeTicketRepository;
use models\summit\ISummitRegistrationPromoCodeRepository;
use models\summit\ISummitRepository;
use models\summit\PresentationSpeaker;
use models\summit\SpeakersRegistrationDiscountCode;
use models\summit\SpeakersSummitRegistrationPromoCode;
use models\summit\Summit;
use models\summit\SummitAttendeeTicket;
use models\summit\SummitRegistrationDiscountCode;
use models\summit\SummitRegistrationPromoCode;
use services\model\ISummitPromoCodeService;
use utils\Filter;
use utils\FilterElement;
use utils\PagingInfo;

/**
 * Class SummitPromoCodeService
 * @package App\Services\Model
 */
final class SummitPromoCodeService
    extends AbstractService
    implements ISummitPromoCodeService
{
    /**
     * @var IMemberRepository
     */
    private $member_repository;

    /**
     * @var ICompanyRepository
     */
    private $company_repository;

    /**
     * @var ISpeakerRepository
     */
    private $speaker_repository;

    /**
     * @var ISummitRepository
     */
    private $summit_repository;

    /**
     * @var ISummitRegistrationPromoCodeRepository
     */
    private $repository;

    /**
     * @var ISummitAttendeeTicketRepository
     */
    private $ticket_repository;

    /**
     * @var ITagRepository
     */
    private $tag_repository;

    /**
     * @var ILockManagerService
     */
    private $lock_service;

    /**
     * @param IMemberRepository $member_repository
     * @param ICompanyRepository $company_repository
     * @param ISpeakerRepository $speaker_repository
     * @param ISummitRepository $summit_repository
     * @param ISummitAttendeeTicketRepository $ticket_repository
     * @param ITagRepository $tag_repository
     * @param ISummitRegistrationPromoCodeRepository $repository
     * @param ITransactionService $tx_service
     * @param ILockManagerService $lock_service
     */
    public function __construct
    (
        IMemberRepository   $member_repository,
        ICompanyRepository  $company_repository,
        ISpeakerRepository  $speaker_repository,
        ISummitRepository   $summit_repository,
        ISummitAttendeeTicketRepository $ticket_repository,
        ITagRepository      $tag_repository,
        ISummitRegistrationPromoCodeRepository $repository,
        ITransactionService $tx_service,
        ILockManagerService $lock_service
    )
    {
        parent::__construct($tx_service);
        $this->member_repository = $member_repository;
        $this->company_repository = $company_repository;
        $this->speaker_repository = $speaker_repository;
        $this->summit_repository = $summit_repository;
        $this->ticket_repository = $ticket_repository;
        $this->tag_repository = $tag_repository;
        $this->repository = $repository;
        $this->lock_service = $lock_service;
    }

    /**
     * @param Summit $summit
     * @param array $data
     * @return array
     * @throws EntityNotFoundException
     */
    private function getPromoCodeParams(Summit $summit, array $data)
    {
        $params = [];

        if (isset($data['allowed_ticket_types'])) {
            $allowed_ticket_types = [];
            foreach ($data['allowed_ticket_types'] as $ticket_type_id) {
                if(empty($ticket_type_id)) continue;
                $ticket_type = $summit->getTicketTypeById(intval($ticket_type_id));
                if (is_null($ticket_type)) continue;
                $allowed_ticket_types[] = $ticket_type;
            }
            $params['allowed_ticket_types'] = $allowed_ticket_types;
        }

        if (isset($data['badge_features'])) {
            $badge_features = [];
            foreach ($data['badge_features'] as $feature_id) {
                if(empty($feature_id)) continue;
                $feature = $summit->getFeatureTypeById(intval($feature_id));
                if (is_null($feature)) continue;
                $badge_features[] = $feature;

            }
            $params['badge_features'] = $badge_features;
        }

        if (isset($data['owner_id'])) {
            $owner = $this->member_repository->getById(intval($data['owner_id']));
            if (is_null($owner))
                throw new EntityNotFoundException(sprintf("owner_id %s not found", $data['owner_id']));
            $params['owner'] = $owner;
        }

        if (isset($data['speaker_id'])) {
            $speaker = $this->speaker_repository->getById(intval($data['speaker_id']));
            if (!is_null($speaker))
                $params['speaker'] = $speaker;
        }

        if (isset($data['sponsor_id'])) {
            $sponsor = $summit->getSummitSponsorById(intval($data['sponsor_id']));
            if (!is_null($sponsor))
                $params['sponsor'] = $sponsor;
        }

        return $params;
    }

    /**
     * @param Summit $summit
     * @param array $data
     * @param Member $current_user
     * @return SummitRegistrationPromoCode
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function addPromoCode(Summit $summit, array $data, Member $current_user = null)
    {
        $promo_code =  $this->tx_service->transaction(function () use ($summit, $data, $current_user) {

            Log::debug
            (
                sprintf
                (
                    "SummitPromoCodeService::addPromoCode summit %s data %s",
                    $summit->getId(),
                    json_encode($data)
                )
            );

            $code = isset($data['code']) ? trim($data['code']) : null;

            if (empty($code)) {
                throw new ValidationException("Code can not be empty.");
            }

            $old_promo_code = $summit->getPromoCodeByCode($code);

            if (!is_null($old_promo_code))
                throw new ValidationException(sprintf("Promo code %s already exits on Summit %s.", trim($data['code']), $summit->getId()));

            $promo_code = SummitPromoCodeFactory::build($summit, $data, $this->getPromoCodeParams($summit, $data));
            if (is_null($promo_code))
                throw new ValidationException(sprintf("class_name %s is invalid", $data['class_name']));

            if (!is_null($current_user))
                $promo_code->setCreator($current_user);

            $promo_code->setSourceAdmin();

            if (isset($data['speaker_ids']) && (
                $promo_code->getClassName() == SpeakersSummitRegistrationPromoCode::ClassName ||
                $promo_code->getClassName() == SpeakersRegistrationDiscountCode::ClassName)) {
                Log::debug
                (
                    sprintf
                    (
                        "SummitPromoCodeService::addPromoCode promo code %s is a speaker promo code",
                        $promo_code->getId()
                    )
                );

                foreach ($data['speaker_ids'] as $speaker_id) {
                    if(empty($speaker_id)) continue;
                    Log::debug
                    (
                        sprintf
                        (
                            "SummitPromoCodeService::addPromoCode promo code %s trying to assign to speaker %s",
                            $promo_code->getId(),
                            $speaker_id
                        )
                    );

                    $speaker = $summit->getSpeaker(intval($speaker_id), false);

                    if(is_null($speaker)) {
                        Log::warning
                        (
                            sprintf("SummitPromoCodeService::addPromoCode Speaker %s not found.", $speaker_id)
                        );
                        continue;
                    }

                    $promo_code->assignSpeaker($speaker);
                }

                $this->repository->add($promo_code, true);
            }

            // tags
            if (isset($data['tags'])) {
                $promo_code->clearTags();

                foreach ($data['tags'] as $tag_value) {
                    if(empty($tag_value)) continue;
                    $tag = $this->tag_repository->getByTag($tag_value);

                    if (is_null($tag)) {
                        $tag = new Tag($tag_value);
                    }
                    $promo_code->addTag($tag);
                }
            }

            return $promo_code;
        });

        if(isset($data['ticket_types_rules'])){
            $promo_code = $this->tx_service->transaction(function () use ($summit, $data, $current_user, $promo_code) {
               foreach ($data['ticket_types_rules'] as $rule){
                   $promo_code = $this->addPromoCodeTicketTypeRule
                     (
                         $summit,
                         $promo_code->getId(),
                         intval($rule['ticket_type_id']),
                         $rule,
                     );
               }
               return $promo_code;
            });
        }

        return $promo_code;
    }

    /**
     * @param Summit $summit
     * @param $promo_code_id
     * @param array $data
     * @param Member|null $current_user
     * @return mixed|SummitRegistrationPromoCode
     * @throws \Exception
     */
    public function updatePromoCode(Summit $summit, $promo_code_id, array $data, Member $current_user = null)
    {
        return $this->tx_service->transaction(function () use ($promo_code_id, $summit, $data, $current_user) {

            Log::debug
            (
                sprintf
                (
                    "SummitPromoCodeService::updatePromoCode summit %s promo code %s payload %s",
                    $summit->getId(),
                    $promo_code_id,
                    json_encode($data)
                )
            );

            $promo_code = $summit->getPromoCodeById($promo_code_id);
            if (is_null($promo_code))
                throw new EntityNotFoundException
                (
                    sprintf
                    (
                        "Promo Code id %s does not belongs to summit id %s.",
                        $promo_code_id,
                        $summit->getId()
                    )
                );

            if (isset($data['code'])) {
                $old_promo_code = $summit->getPromoCodeByCode(trim($data['code']));

                if (!is_null($old_promo_code) && $old_promo_code->getId() != $promo_code_id)
                    throw new ValidationException(sprintf("Promo Code %s already exits on summit id %s for promo code id %s.", trim($data['code']), $summit->getId(), $old_promo_code->getId()));

            }

            // tags
            if (isset($data['tags'])) {
                $promo_code->clearTags();

                foreach ($data['tags'] as $tag_value) {
                    $tag = $this->tag_repository->getByTag($tag_value);

                    if (is_null($tag)) {
                        $tag = new Tag($tag_value);
                    }
                    $promo_code->addTag($tag);
                }
            }

            $promo_code = SummitPromoCodeFactory::populate
            (
                $promo_code, $summit, $data, $this->getPromoCodeParams($summit, $data)
            );

            if (!is_null($current_user) && !$promo_code->hasCreator())
                $promo_code->setCreator($current_user);

            $promo_code->setSourceAdmin();

            $badge_features_apply_to_all_tix_retroactively = $data['badge_features_apply_to_all_tix_retroactively'] ?? false;

            if($badge_features_apply_to_all_tix_retroactively){
                Log::debug(sprintf("SummitPromoCodeService::updatePromoCode summit %s promo code %s triggering retro actively apply features", $summit->getId(), $promo_code_id));

                // trigger background job to re apply to all tickets with this promo code
                ReApplyPromoCodeRetroActively::dispatch($promo_code_id)->afterResponse();
            }

            return $promo_code;
        });
    }

    /**
     * @param Summit $summit
     * @param int $promo_code_id
     * @return void
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function deletePromoCode(Summit $summit, $promo_code_id)
    {
        $this->tx_service->transaction(function () use ($promo_code_id, $summit) {

            $promo_code = $summit->getPromoCodeById($promo_code_id);
            if (is_null($promo_code))
                throw new EntityNotFoundException
                (
                    trans
                    (
                        'not_found_errors.promo_code_delete_code_not_found',
                        ['promo_code_id' => $promo_code_id, 'summit_id' => $summit->getId()]
                    )
                );

            if ($promo_code->isEmailSent())
                throw new ValidationException(trans('validation_errors.promo_code_delete_already_sent'));

            if ($promo_code->isRedeemed())
                throw new ValidationException(trans('validation_errors.promo_code_delete_already_redeemed'));

            $summit->removePromoCode($promo_code);

        });
    }

    /**
     * @param Summit $summit
     * @param int $promo_code_id
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function sendPromoCodeMail(Summit $summit, $promo_code_id)
    {
        $this->tx_service->transaction(function () use ($promo_code_id, $summit) {

            $promo_code = $summit->getPromoCodeById($promo_code_id);
            if (is_null($promo_code))
                throw new EntityNotFoundException(trans('not_found_errors.promo_code_email_code_not_found', ['promo_code_id' => $promo_code_id, 'summit_id' => $summit->getId()]));

            $name = null;
            $email = null;

            if ($promo_code instanceof IOwnablePromoCode) {
                $name = $promo_code->getOwnerFullname();
                $email = $promo_code->getOwnerEmail();
            }

            if (empty($email)) {
                throw new ValidationException(trans("validation_errors.promo_code_email_send_empty_email"));
            }

            if (empty($name)) {
                throw new ValidationException(trans("validation_errors.promo_code_email_send_empty_name"));
            }

            PromoCodeEmailFactory::send($promo_code);
            $promo_code->markSent();
        });
    }

    /**
     * @param Summit $summit
     * @param int $promo_code_id
     * @param int $ticket_type_id
     * @param array $data
     * @return SummitRegistrationPromoCode
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function addPromoCodeTicketTypeRule(Summit $summit, int $promo_code_id, int $ticket_type_id, array $data): SummitRegistrationPromoCode
    {
        return $this->tx_service->transaction(function () use ($summit, $promo_code_id, $ticket_type_id, $data) {
            $promo_code = $summit->getPromoCodeById($promo_code_id);
            if (is_null($promo_code))
                throw new EntityNotFoundException("promo code not found");

            $ticket_type = $summit->getTicketTypeById($ticket_type_id);
            if (is_null($ticket_type))
                throw new EntityNotFoundException("ticket type not found");

            if ($promo_code instanceof SummitRegistrationDiscountCode) {
                $data['ticket_type'] = $ticket_type;
                if($ticket_type->isFree())
                    throw new ValidationException(sprintf("Ticket Type %s (Free) can not be added on added on Discount Code.", $ticket_type_id ));

                $rule = SummitRegistrationDiscountCodeTicketTypeRuleFactory::build($data);
                $promo_code->addTicketTypeRule($rule);

                return $promo_code;
            }

            $promo_code->addAllowedTicketType($ticket_type);

            return $promo_code;

        });
    }

    /**
     * @param Summit $summit
     * @param int $promo_code_id
     * @param int $ticket_type_id
     * @return SummitRegistrationPromoCode
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function removePromoCodeTicketTypeRule(Summit $summit, int $promo_code_id, int $ticket_type_id): SummitRegistrationPromoCode
    {
        return $this->tx_service->transaction(function () use ($summit, $promo_code_id, $ticket_type_id) {
            $promo_code = $summit->getPromoCodeById($promo_code_id);
            if (is_null($promo_code))
                throw new EntityNotFoundException("promo code not found");

            $ticket_type = $summit->getTicketTypeById($ticket_type_id);
            if (is_null($ticket_type))
                throw new EntityNotFoundException("ticket type not found");

            if ($promo_code instanceof SummitRegistrationDiscountCode) {
                $promo_code->removeTicketTypeRuleForTicketType($ticket_type);
                return $promo_code;
            }

            $promo_code->removeAllowedTicketType($ticket_type);

            return $promo_code;
        });
    }

    /**
     * @param Summit $summit
     * @param int $promo_code_id
     * @param int $badge_feature_id
     * @return SummitRegistrationPromoCode
     * @throws \Exception
     */
    public function addPromoCodeBadgeFeature(Summit $summit, int $promo_code_id, int $badge_feature_id): SummitRegistrationPromoCode
    {
        return $this->tx_service->transaction(function () use ($summit, $promo_code_id, $badge_feature_id) {
            $promo_code = $summit->getPromoCodeById($promo_code_id);
            if (is_null($promo_code))
                throw new EntityNotFoundException("promo code not found");

            $badge_feature = $summit->getFeatureTypeById($badge_feature_id);
            if (is_null($badge_feature))
                throw new EntityNotFoundException("badge feature not found");

            $promo_code->addBadgeFeatureType($badge_feature);

            return $promo_code;
        });
    }

    /**
     * @param Summit $summit
     * @param int $promo_code_id
     * @param int $badge_feature_id
     * @return SummitRegistrationPromoCode
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function removePromoCodeBadgeFeature(Summit $summit, int $promo_code_id, int $badge_feature_id): SummitRegistrationPromoCode
    {
        return $this->tx_service->transaction(function () use ($summit, $promo_code_id, $badge_feature_id) {
            $promo_code = $summit->getPromoCodeById($promo_code_id);
            if (is_null($promo_code))
                throw new EntityNotFoundException("promo code not found");

            $badge_feature = $summit->getFeatureTypeById($badge_feature_id);
            if (is_null($badge_feature))
                throw new EntityNotFoundException("badge feature not found");

            $promo_code->removeBadgeFeatureType($badge_feature);

            return $promo_code;
        });
    }

    /**
     * @param Summit $summit
     * @param UploadedFile $csv_file
     * @param Member|null $current_user
     * @throws ValidationException
     */
    public function importPromoCodes(Summit $summit, UploadedFile $csv_file, ?Member $current_user = null): void
    {
        Log::debug(sprintf("SummitPromoCodeService::importPromoCodes - summit %s", $summit->getId()));

        $allowed_extensions = ['txt'];

        if (!in_array($csv_file->extension(), $allowed_extensions)) {
            throw new ValidationException("File does not has a valid extension ('csv').");
        }

        $csv_data = File::get($csv_file->getRealPath());

        if (empty($csv_data))
            throw new ValidationException("File content is empty.");

        $reader = CSVReader::buildFrom($csv_data);

        // check needed columns (headers names)

        if (!$reader->hasColumn("code"))
            throw new ValidationException("File is missing code column.");
        if (!$reader->hasColumn("class_name"))
            throw new ValidationException("File is missing class_name column.");
        if (!$reader->hasColumn("quantity_available"))
            throw new ValidationException("File is missing quantity_available column.");

        foreach ($reader as $idx => $row) {
            try {
                if(isset($row['badge_features'])){
                    $row['badge_features'] = explode('|', $row['badge_features']);
                }

                if(isset($row['allowed_ticket_types'])){
                    $row['allowed_ticket_types'] = explode('|', $row['allowed_ticket_types']);
                }

                if(isset($row['speaker_ids'])){
                    $row['speaker_ids'] = explode('|', $row['speaker_ids']);
                }

                if(isset($row['tags'])){
                    $row['tags'] = explode('|', $row['tags']);
                }

                if(isset($row['ticket_types_rules']) && (isset($row['amount']) || isset($row['rate']))){

                    $row['ticket_types_rules'] = explode('|', $row['ticket_types_rules']);

                    $ticket_types_rules = [];

                    foreach ($row['ticket_types_rules'] as $ticket_type_id){
                        $ticket_types_rules[] = [
                            'ticket_type_id' => intval($ticket_type_id),
                            'amount' => $row['amount'] ?? 0.0,
                            'rate' =>  $row['rate']?? 0.0
                        ];
                    }
                    $row['ticket_types_rules'] = $ticket_types_rules;
                }

                Log::debug(sprintf("SummitPromoCodeService::importPromoCodes processing row %s", json_encode($row)));
                $code = trim($row['code']);
                $promo_code = $summit->getPromoCodeByCode($code);
                if(is_null($promo_code))
                    $this->addPromoCode($summit, $row, $current_user);
                else
                    $this->updatePromoCode($summit, $promo_code->getId(),$row, $current_user);
            } catch (\Exception $ex) {
                Log::warning($ex);
                $summit = $this->summit_repository->getById($summit->getId());
            }
        }
    }

    /**
     * @param int $promo_code_id
     * @throws EntityNotFoundException
     */
    public function reApplyPromoCode(int $promo_code_id):void{

        Log::debug(sprintf("SummitPromoCodeService::reApplyPromoCode promo code id %s", $promo_code_id));

        $page = 1;
        $count = 0;
        $maxPageSize = 100;
        $promo_code = $this->tx_service->transaction(function() use ($promo_code_id){
            $promo_code = $this->repository->getById($promo_code_id);
            if(!$promo_code instanceof SummitRegistrationPromoCode)
                throw new EntityNotFoundException("Promo Code not found.");

            return $promo_code;
        });

        $filter = new Filter();
        $filter->addFilterCondition(FilterElement::makeEqual('promo_code_id', $promo_code_id));

        do {

            $ticket_ids = $this->tx_service->transaction(function () use ($filter, $page, $maxPageSize) {
                return $this->ticket_repository->getAllIdsByPage(new PagingInfo($page, $maxPageSize), $filter);
            });

            if (!count($ticket_ids)) {
                // if we are processing a page, then break it
                Log::debug(sprintf("SummitPromoCodeService::reApplyPromoCode promo code id %s page is empty, ending processing.", $promo_code_id));
                break;
            }

            foreach ($ticket_ids as $ticket_id){
                try {

                    Log::debug(sprintf("SummitPromoCodeService::reApplyPromoCode processing ticket %s", $ticket_id));

                    $this->tx_service->transaction(function () use ($ticket_id, $promo_code) {

                        $ticket = $this->ticket_repository->getById($ticket_id);

                        if (!$ticket instanceof SummitAttendeeTicket)
                            throw new EntityNotFoundException("Ticket not found.");

                        if (!$ticket->hasBadge())
                            throw new ValidationException(sprintf("Ticket %s does not has a Badge set.", $ticket_id));


                        Log::debug(sprintf("SummitPromoCodeService::reApplyPromoCode reapplying promo code %s to ticket %s", $promo_code->getId(), $ticket_id));

                        $ticket->getBadge()->applyPromoCode($promo_code);

                    });

                    $count++;
                }
                catch (\Exception $ex){
                    Log::warning($ex);
                }
            }
            $page++;
        } while(1);
    }

    /**
     * @param SummitRegistrationPromoCode $promo_code
     * @param int $speaker_id
     * @return SummitRegistrationPromoCode
     * @throws \Exception
     */
    public function addPromoCodeSpeaker(SummitRegistrationPromoCode $promo_code, int $speaker_id): SummitRegistrationPromoCode
    {
        return $this->tx_service->transaction(function () use ($promo_code, $speaker_id) {
            if (!$promo_code instanceof SpeakersSummitRegistrationPromoCode && !$promo_code instanceof SpeakersRegistrationDiscountCode)
                throw new ValidationException("Invalid Promo Code.");

            $speaker = $this->speaker_repository->getById($speaker_id);
            if (!$speaker instanceof PresentationSpeaker)
                throw new EntityNotFoundException("Speaker not found.");

            $assignment = $promo_code->getSpeakerAssignment($speaker);
            if(!is_null($assignment))
                throw new ValidationException("Speaker already assigned.");

            $promo_code->assignSpeaker($speaker);

            return $promo_code;
        });
    }

    /**
     * @param SummitRegistrationPromoCode $promo_code
     * @param int $speaker_id
     * @return SummitRegistrationPromoCode
     * @throws \Exception
     */
    public function removePromoCodeSpeaker(SummitRegistrationPromoCode $promo_code, int $speaker_id): SummitRegistrationPromoCode
    {
        return $this->tx_service->transaction(function () use ($promo_code, $speaker_id) {
            if (!$promo_code instanceof SpeakersSummitRegistrationPromoCode && !$promo_code instanceof SpeakersRegistrationDiscountCode)
                throw new ValidationException("Invalid Promo Code.");

            $speaker = $this->speaker_repository->getById($speaker_id);
            if (!$speaker instanceof PresentationSpeaker)
                throw new EntityNotFoundException("Speaker not found.");

            $assignment = $promo_code->getSpeakerAssignment($speaker);
            if(is_null($assignment))
                throw new EntityNotFoundException("Speaker not found.");

            $promo_code->unassignSpeaker($speaker);

            return $promo_code;
        });
    }

    /**
     * @param Summit $summit
     * @param Member $owner
     * @param string $promo_code_value
     * @param Filter $filter
     * @return void
     * @throws \Exception
     */
    public function preValidatePromoCode(Summit $summit, Member $owner, string $promo_code_value, Filter $filter):void
    {
        $this->tx_service->transaction(function () use ($summit, $owner, $promo_code_value, $filter) {

            $ticket_type_id = intval($filter->getUniqueFilter('ticket_type_id')->getValue());

            $ticket_type = $summit->getTicketTypeById($ticket_type_id);
            if (is_null($ticket_type)) {
                throw new EntityNotFoundException(sprintf("Ticket Type %s not found on summit %s.", $ticket_type_id, $summit->getId()));
            }

            $ticket_type_subtype = $filter->getUniqueFilter('ticket_type_subtype')->getValue();
            $qty = intval($filter->getUniqueFilter('ticket_type_qty')->getValue());

            $validator = PromoCodeValidationStrategyFactory::createStrategy($ticket_type, $ticket_type_subtype, $qty, $owner);

            $promo_code = $this->repository->getByValueExclusiveLock($summit, $promo_code_value);

            if (!$promo_code instanceof SummitRegistrationPromoCode || $promo_code->getSummitId() != $summit->getId() || !$validator->isValid($promo_code)){
                throw new ValidationException(sprintf('The Promo Code "%s" is not a valid code.', $promo_code_value));
            }

        });
    }

    /**
     * @inheritDoc
     */
    public function triggerSendSponsorPromoCodes(Summit $summit, array $payload, Filter $filter = null): void
    {
        ProcessSponsorPromoCodesJob::dispatch($summit, $payload, $filter);
    }

    use ParametrizedSendEmails;

    /**
     * @inheritDoc
     */
    public function sendSponsorPromoCodes(int $summit_id, array $payload, Filter $filter = null): void
    {
        $this->_sendEmails(
            $summit_id,
            $payload,
            "sponsor promo codes",
            function ($summit, $paging_info, $filter, $resetPage) {

                if (!$filter->hasFilter("summit_id"))
                    $filter->addFilterCondition(FilterElement::makeEqual('summit_id', $summit->getId()));

                if ($filter->hasFilter("email_sent")) {
                    $isSentFilter = $filter->getUniqueFilter("email_sent");
                    $email_sent = $isSentFilter->getBooleanValue();
                    Log::debug(sprintf("SummitPromoCodesService::send is_sent filter value %b", $email_sent));
                    if (!$email_sent && is_callable($resetPage)) {
                        // we need to reset the page bc the page processing will mark the current page as "sent"
                        // and adding an offset will move the cursor forward, leaving next round of not send out of the current process
                        Log::debug("SummitPromoCodesService::send resetting page bc email_sent filter is false");
                        $resetPage();
                    }
                }
                return $this->repository->getIdsBySummit($summit, $paging_info, $filter)->getItems();
            },
            function ($summit, $flow_event, $promocode_id, $test_email_recipient, $announcement_email_config, $filter) use ($payload) {
                try {
                    $this->tx_service->transaction(function () use (
                        $summit,
                        $flow_event,
                        $promocode_id,
                        $test_email_recipient,
                        $filter,
                        $payload
                    ) {
                        $promo_code = $this->tx_service->transaction(function () use ($flow_event, $promocode_id) {

                            Log::debug(sprintf("SummitPromoCodeService::send processing promocode id  %s", $promocode_id));

                            $promo_code = $this->repository->getByIdExclusiveLock(intval($promocode_id));
                            if (!$promo_code instanceof SummitRegistrationPromoCode)
                                return null;

                            return $promo_code;
                        });

                        // send email
                        if ($flow_event == SponsorPromoCodeEmail::EVENT_SLUG && !is_null($promo_code))
                            SponsorPromoCodeEmail::dispatch($promo_code, $test_email_recipient);
                    });
                } catch (\Exception $ex) {
                    Log::warning($ex);
                }
            },
            null,
            $filter
        );
    }
}