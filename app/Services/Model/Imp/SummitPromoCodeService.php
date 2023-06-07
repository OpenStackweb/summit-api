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

use App\Jobs\Emails\Registration\PromoCodeEmailFactory;
use App\Jobs\ReApplyPromoCodeRetroActively;
use App\Models\Foundation\Summit\Factories\SummitPromoCodeFactory;
use App\Models\Foundation\Summit\Factories\SummitRegistrationDiscountCodeTicketTypeRuleFactory;
use App\Services\Utils\CSVReader;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
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
use models\summit\ISummitRepository;
use models\summit\PresentationSpeaker;
use models\summit\SpeakersRegistrationDiscountCode;
use models\summit\SpeakersSummitRegistrationPromoCode;
use models\summit\Summit;
use models\summit\SummitAttendee;
use models\summit\SummitAttendeeTicket;
use models\summit\SummitRegistrationDiscountCode;
use models\summit\SummitRegistrationPromoCode;
use services\model\ISummitPromoCodeService;
use models\summit\ISummitRegistrationPromoCodeRepository;
use models\summit\ISummitAttendeeTicketRepository;
use utils\Filter;
use utils\FilterElement;
use utils\FilterParser;
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
     * @param IMemberRepository $member_repository
     * @param ICompanyRepository $company_repository
     * @param ISpeakerRepository $speaker_repository
     * @param ISummitRepository $summit_repository
     * @param ISummitAttendeeTicketRepository $ticket_repository
     * @param ITagRepository $tag_repository
     * @param ISummitRegistrationPromoCodeRepository $repository
     * @param ITransactionService $tx_service
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
        ITransactionService $tx_service
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
                $ticket_type = $summit->getTicketTypeById(intval($ticket_type_id));
                if (is_null($ticket_type))
                    throw new EntityNotFoundException(sprintf("ticket type %s not found", $ticket_type_id));
                $allowed_ticket_types[] = $ticket_type;
            }
            $params['allowed_ticket_types'] = $allowed_ticket_types;
        }

        if (isset($data['badge_features'])) {
            $badge_features = [];
            foreach ($data['badge_features'] as $feature_id) {
                $feature = $summit->getFeatureTypeById(intval($feature_id));
                if (is_null($feature))
                    throw new EntityNotFoundException(sprintf("badge feature %s not found", $feature_id));
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
            if (is_null($speaker))
                throw new EntityNotFoundException(sprintf("speaker_id %s not found", $data['speaker_id']));
            $params['speaker'] = $speaker;
        }

        if (isset($data['sponsor_id'])) {
            $sponsor = $this->company_repository->getById(intval($data['sponsor_id']));
            if (is_null($sponsor))
                throw new EntityNotFoundException(sprintf("sponsor_id %s not found", $data['sponsor_id']));
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
            Log::debug(sprintf("SummitPromoCodeService::addPromoCode summit %s data %s", $summit->getId(), json_encode($data)));

            $code = trim($data['code']);

            if (empty($code)) {
                throw new ValidationException("code can not be empty!");
            }

            $old_promo_code = $summit->getPromoCodeByCode($code);

            if (!is_null($old_promo_code))
                throw new ValidationException(sprintf("promo code %s already exits on summit id %s", trim($data['code']), $summit->getId()));

            $promo_code = SummitPromoCodeFactory::build($summit, $data, $this->getPromoCodeParams($summit, $data));
            if (is_null($promo_code))
                throw new ValidationException(sprintf("class_name %s is invalid", $data['class_name']));

            if (!is_null($current_user))
                $promo_code->setCreator($current_user);

            $promo_code->setSourceAdmin();

            if (isset($data['speaker_ids']) && ($promo_code->getClassName() == SpeakersSummitRegistrationPromoCode::ClassName ||
                $promo_code->getClassName() == SpeakersRegistrationDiscountCode::ClassName)) {

                foreach ($data['speaker_ids'] as $speaker_id) {
                    $speaker = $summit->getSpeaker($speaker_id);
                    if(is_null($speaker))
                        throw new EntityNotFoundException(sprintf("speaker %s not found", $speaker_id));
                    $promo_code->assignSpeaker($speaker);
                }
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
     * @param int $promo_code_id
     * @param array $data
     * @param Member $current_user
     * @return SummitRegistrationPromoCode
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function updatePromoCode(Summit $summit, $promo_code_id, array $data, Member $current_user = null)
    {
        return $this->tx_service->transaction(function () use ($promo_code_id, $summit, $data, $current_user) {

            Log::debug(sprintf("SummitPromoCodeService::updatePromoCode summit %s promo code %s payload %s", $summit->getId(), $promo_code_id, json_encode($data)));

            $promo_code = $summit->getPromoCodeById($promo_code_id);
            if (is_null($promo_code))
                throw new EntityNotFoundException(sprintf("Promo Code id %s does not belongs to summit id %s.", $promo_code_id, $summit->getId()));

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

            $promo_code = SummitPromoCodeFactory::populate($promo_code, $summit, $data, $this->getPromoCodeParams($summit, $data));

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
            $promo_code->setEmailSent(true);
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
                throw new ValidationException("invalid promo code");

            $speaker = $this->speaker_repository->getById($speaker_id);
            if (!$speaker instanceof PresentationSpeaker)
                throw new EntityNotFoundException("speaker not found");

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
                throw new ValidationException("invalid promo code");

            $speaker = $this->speaker_repository->getById($speaker_id);
            if (!$speaker instanceof PresentationSpeaker)
                throw new EntityNotFoundException("speaker not found");

            //can't remove sent or redeemed associations
            $assignment = $promo_code->getSpeakerAssignment($speaker);
            if (!is_null($assignment) && ($assignment->isSent() || $assignment->isRedeemed())) return $promo_code;

            $promo_code->unassignSpeaker($speaker);

            return $promo_code;
        });
    }
}