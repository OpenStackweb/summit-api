<?php namespace App\Services\Model\Imp;
/**
 * Copyright 2019 OpenStack Foundation
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

use App\Models\Foundation\ExtraQuestions\ExtraQuestionType;
use App\Models\Foundation\ExtraQuestions\ExtraQuestionTypeConstants;
use App\Models\Foundation\Summit\Factories\SummitBadgeFeatureTypeFactory;
use App\Models\Foundation\Summit\Factories\SummitOrderFactory;
use App\Models\Foundation\Summit\Factories\SummitPromoCodeFactory;
use App\Models\Foundation\Summit\Factories\SummitTicketTypeFactory;
use App\Models\Foundation\Summit\Repositories\ISummitOrderRepository;
use App\Services\Apis\ExternalRegistrationFeeds\IExternalRegistrationFeedFactory;
use App\Services\Model\AbstractService;
use App\Services\Model\dto\ExternalUserDTO;
use App\Services\Model\ICompanyService;
use App\Services\Model\IMemberService;
use App\Services\Model\IRegistrationIngestionService;
use libs\utils\ITransactionService;
use models\exceptions\ValidationException;
use models\main\ICompanyRepository;
use models\main\IMemberRepository;
use models\summit\factories\SummitAttendeeFactory;
use models\summit\ISummitAttendeeTicketRepository;
use models\summit\ISummitRepository;
use models\summit\Summit;
use Exception;
use Illuminate\Support\Facades\Log;
use models\summit\SummitAttendee;
use models\summit\SummitAttendeeTicket;
use models\summit\SummitBadgeFeatureType;
use models\summit\SummitBadgeType;
use models\summit\SummitOrderExtraQuestionAnswer;
use models\summit\SummitRegistrationDiscountCode;
use models\summit\ISummitAttendeeRepository;
use models\summit\SummitRegistrationPromoCode;
use services\apis\IEventbriteAPI;

/**
 * Class RegistrationIngestionService
 * @package App\Services\Model\Imp
 */
final class RegistrationIngestionService
    extends AbstractService implements IRegistrationIngestionService
{

    /**
     * @var IMemberService
     */
    private $member_service;
    /**
     * @var ISummitRepository
     */
    private $summit_repository;

    /**
     * @var IExternalRegistrationFeedFactory
     */
    private $feed_factory;

    /**
     * @var ISummitOrderRepository
     */
    private $order_repository;

    /**
     * @var ISummitAttendeeTicketRepository
     */
    private $ticket_repository;

    /**
     * @var IMemberRepository
     */
    private $member_repository;

    /**
     * @var ISummitAttendeeRepository
     */
    private $attendee_repository;

    /**
     * @var ICompanyService
     */
    private $company_service;

    /**
     * @var ICompanyRepository
     */
    private $company_repository;

    /**
     * @param IMemberService $member_service
     * @param ISummitRepository $summit_repository
     * @param IExternalRegistrationFeedFactory $feed_factory
     * @param ISummitOrderRepository $order_repository
     * @param ISummitAttendeeTicketRepository $ticket_repository
     * @param IMemberRepository $member_repository
     * @param ISummitAttendeeRepository $attendee_repository
     * @param ICompanyRepository $company_repository
     * @param ICompanyService $company_service
     * @param ITransactionService $tx_service
     */
    public function __construct
    (
        IMemberService                   $member_service,
        ISummitRepository                $summit_repository,
        IExternalRegistrationFeedFactory $feed_factory,
        ISummitOrderRepository           $order_repository,
        ISummitAttendeeTicketRepository  $ticket_repository,
        IMemberRepository                $member_repository,
        ISummitAttendeeRepository        $attendee_repository,
        ICompanyRepository               $company_repository,
        ICompanyService                  $company_service,
        ITransactionService              $tx_service
    )
    {
        parent::__construct($tx_service);
        $this->member_service = $member_service;
        $this->summit_repository = $summit_repository;
        $this->feed_factory = $feed_factory;
        $this->order_repository = $order_repository;
        $this->ticket_repository = $ticket_repository;
        $this->member_repository = $member_repository;
        $this->attendee_repository = $attendee_repository;
        $this->company_repository = $company_repository;
        $this->company_service = $company_service;
    }

    /**
     * @param $summit_id
     * @param $index
     * @param $external_attendee
     * @return void
     */
    public function ingestExternalAttendee($summit_id, $index, $external_attendee):?SummitAttendee{
        Log::debug
        (
            sprintf
            (
                "RegistrationIngestionService::ingestSummit: processing index %s external attendee %s",
                $index,
                json_encode($external_attendee)
            )
        );

        try {
            return $this->tx_service->transaction(function () use ($summit_id, $external_attendee) {

                $summit = $this->summit_repository->getById($summit_id);
                if (!$summit instanceof Summit) return null;
                $default_badge_type = $summit->getDefaultBadgeType();

                if (!$summit instanceof Summit) return null;

                $external_attendee_profile = $external_attendee['profile'] ?? null;
                $external_promo_code = $external_attendee['promotional_code'] ?? null;
                $ticket_class = $external_attendee['ticket_class'] ?? null;
                $external_order = $external_attendee['order'] ?? null;
                $refunded = $external_attendee['refunded'] ?? false;
                $cancelled = $external_attendee['cancelled'] ?? false;

                $ticket_type = $summit->getTicketTypeByExternalId($ticket_class['id']);
                if (is_null($ticket_type)) {
                    // create ticket type if it does not exists
                    Log::debug(sprintf("RegistrationIngestionService::ingestSummit: ticket class %s does not exists", $ticket_class['id']));
                    $ticket_type = SummitTicketTypeFactory::build(
                        $summit, [
                            'name' => $ticket_class['name'],
                            'description' => $ticket_class['description'],
                            'external_id' => $ticket_class['id'],
                            'quantity_2_sell' => $ticket_class['quantity_total'],
                            'cost' => $ticket_class['cost']['major_value'],
                            'currency' => $ticket_class['cost']['currency'],
                        ]
                    );

                    $summit->addTicketType($ticket_type);
                }

                // trying to get the order by external id
                $order = $this->order_repository->getByExternalIdAndSummitLockExclusive($summit, $external_order['id']);
                if (is_null($order)) {
                    // create it if it does not exists ...
                    Log::debug(sprintf("RegistrationIngestionService::ingestSummit: order %s does not exists", $external_order['id']));

                    $owner_order_email = trim($external_order['email']);
                    $order = SummitOrderFactory::build($summit, [
                        'external_id' => $external_order['id'],
                        'owner_first_name' => $external_order['first_name'],
                        'owner_last_name' => $external_order['last_name'],
                        'owner_email' => $owner_order_email,
                    ]);

                    $order->setSummit($summit);

                    $order->generateNumber();

                    $owner = $this->member_repository->getByEmail($owner_order_email);

                    if (is_null($owner)) {
                        // check if we have an external one
                        try {
                            Log::debug
                            (
                                sprintf
                                (
                                    "RegistrationIngestionService::ingestSummit order owner does not exist for email %s, trying to get it externally"
                                    , $owner_order_email
                                )
                            );

                            $user = $this->member_service->checkExternalUser($owner_order_email);

                            if (!is_null($user)) {
                                // we have an user on idp
                                $external_id = $user['id'];
                                Log::debug
                                (
                                    sprintf
                                    (
                                        "RegistrationIngestionService::ingestSummit got external user %s for email %s",
                                        $external_id, $owner_order_email
                                    )
                                );

                                try {
                                    // possible race condition
                                    $owner = $this->member_service->registerExternalUser
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
                                    // race condition lost, try to get it
                                    Log::warning($ex);
                                    $owner = $this->member_repository->getByExternalIdExclusiveLock(intval($external_id));
                                }
                            }
                        } catch (Exception $ex) {
                            Log::warning($ex);
                        }
                    }

                    if (!is_null($owner)) {
                        $owner->addSummitRegistrationOrder($order);
                    }

                    do {
                        if (!$summit->existOrderNumber($order->getNumber()))
                            break;
                        $order->generateNumber();
                    } while (1);

                    // generate the key to access
                    $order->generateHash();
                    $order->generateQRCode();

                    $summit->addOrder($order);
                }

                // try to get the existent ticket
                $ticket = $this->ticket_repository->getBySummitAndExternalOrderIdAndExternalAttendeeIdExclusiveLock
                (
                    $summit,
                    $external_order['id'],
                    $external_attendee['id']
                );

                if (is_null($ticket)) {
                    // create it if it does not exists ..
                    Log::debug
                    (
                        sprintf
                        (
                            "RegistrationIngestionService::ingestSummit ticket %s - %s does not exists",
                            $external_order['id'],
                            $external_attendee['id']
                        )
                    );

                    $ticket = new SummitAttendeeTicket();
                    $ticket->setExternalAttendeeId($external_attendee['id']);
                    $ticket->setExternalOrderId($external_order['id']);
                    $ticket->setBoughtDate(new \DateTime($external_attendee['created'] ?? 'now', new \DateTimeZone('UTC')));
                    $ticket->setOrder($order);
                    $ticket->generateNumber();

                    do {

                        if (!$this->ticket_repository->existNumber($ticket->getNumber()))
                            break;
                        $ticket->generateNumber();
                    } while (1);

                    $ticket->setTicketType($ticket_type);
                }

                // default badge, if ticket it does not already has it
                if (!$ticket->hasBadge()) {
                    $ticket->setBadge(SummitBadgeType::buildBadgeFromType($default_badge_type));
                }

                // check promo code ..,
                if (!is_null($external_promo_code)) {

                    Log::debug(sprintf("RegistrationIngestionService::ingestSummit processing promo code %s", json_encode($external_promo_code)));
                    // has promo code
                    $promo_code = $summit->getPromoCodeByCode($external_promo_code['code']);
                    if (is_null($promo_code)) {

                        Log::debug
                        (
                            sprintf
                            (
                                "RegistrationIngestionService::ingestSummit promo code %s (%s) type %s does not exists"
                                , $external_promo_code['id']
                                , $external_promo_code['code']
                                , $external_promo_code['promotion_type']
                            )
                        );

                        $promo_code_params = [
                            'class_name' => $external_promo_code['promotion_type'] == 'discount' ?
                                SummitRegistrationDiscountCode::ClassName :
                                SummitRegistrationPromoCode::ClassName,
                            'code' => trim($external_promo_code['code']),
                            'external_id' => trim($external_promo_code['id'])
                        ];

                        if ($external_promo_code['promotion_type'] == 'discount') {
                            if (isset($external_promo_code['percent_off'])) {
                                $promo_code_params['rate'] = floatval($external_promo_code['percent_off']);
                            }
                            if (isset($external_promo_code['amount_off'])) {
                                $amount_off = $external_promo_code['amount_off'];
                                if (isset($amount_off['major_value']))
                                    $promo_code_params['amount'] = floatval($amount_off['major_value']);
                            }
                        }

                        $promo_code = SummitPromoCodeFactory::build($summit, $promo_code_params);
                        $summit->addPromoCode($promo_code);
                    }

                    $promo_code->applyTo($ticket);
                }

                // assign attendee
                // check if we have already an attendee on this summit
                $attendee_email = trim($external_attendee_profile['email']);
                $first_name = trim($external_attendee_profile['first_name']);
                $last_name = trim($external_attendee_profile['last_name']);
                $company = isset($external_attendee_profile['company']) ? trim($external_attendee_profile['company']) : '';
                $attendee_owner = $this->member_repository->getByEmail($attendee_email);

                if (is_null($attendee_owner)) {
                    // check if we have an external one
                    try {
                        Log::debug
                        (
                            sprintf
                            (
                                "RegistrationIngestionService::ingestSummit attendee owner does not exist for email %s, trying to get it externally"
                                , $attendee_email
                            )
                        );

                        $user = $this->member_service->checkExternalUser($attendee_email);

                        if (!is_null($user)) {
                            // we have an user on idp
                            $external_id = $user['id'];
                            Log::debug
                            (
                                sprintf
                                (
                                    "RegistrationIngestionService::ingestSummit got external user %s for email %s",
                                    $external_id, $attendee_email
                                )
                            );

                            try {
                                // possible race condition
                                $attendee_owner = $this->member_service->registerExternalUser
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
                                // race condition lost, try to get it
                                Log::warning($ex);
                                $attendee_owner = $this->member_repository->getByExternalIdExclusiveLock(intval($external_id));
                            }
                        }
                    } catch (Exception $ex) {
                        Log::warning($ex);
                    }
                }

                Log::debug
                (
                    sprintf
                    (
                        "RegistrationIngestionService::ingestSummit looking for attendee %s , %s (%s)"
                        , $first_name
                        , $last_name
                        , $attendee_email
                    )
                );

                $attendee = $this->attendee_repository->getBySummitAndExternalId($summit, $external_attendee['id']);

                if (is_null($attendee)) {
                    // try to get it only by email
                    $attendee = $this->attendee_repository->getBySummitAndEmail($summit, $attendee_email);
                }

                if (is_null($attendee)) {
                    Log::debug(sprintf("RegistrationIngestionService::ingestSummit attendee %s does not exists", $attendee_email));
                    $attendee = SummitAttendeeFactory::build($summit, [
                        'external_id' => $external_attendee['id'],
                        'first_name' => $first_name,
                        'last_name' => $last_name,
                        'company' => $company,
                        'email' => $attendee_email
                    ], $attendee_owner);
                    $summit->addAttendee($attendee);
                } else {
                    // update it
                    SummitAttendeeFactory::populate($summit, $attendee, [
                        'first_name' => $first_name,
                        'last_name' => $last_name,
                        'company' => $company,
                        'email' => $attendee_email,
                    ], $attendee_owner);
                }

                // extra questions answers ...
                $attendee->clearExtraQuestionAnswers();
                $answers = $external_attendee['answers'] ?? [];

                foreach ($answers as $answerDTO) {
                    $external_question_id = $answerDTO['question_id'];
                    $value = $answerDTO['answer'] ?? null;
                    if (empty($value)) continue;
                    $question = $summit->getExtraQuestionTypeByExternalId($external_question_id);
                    if (is_null($question)) {
                        Log::debug(sprintf("RegistrationIngestionService::ingestSummit question not found ( external id %s )", $external_question_id));
                        continue;
                    }

                    if ($question->allowsValues()) {
                        $values = explode(IEventbriteAPI::QuestionChoicesCharSeparator, $value);
                        $res = [];
                        foreach ($values as $val) {
                            $v = $question->getValueByName(trim($val));
                            if (!is_null($v))
                                $res[] = $v->getId();
                        }
                        $value = implode(ExtraQuestionType::QuestionChoicesCharSeparator, $res);
                    }

                    $answer = new SummitOrderExtraQuestionAnswer();
                    $answer->setQuestion($question);
                    if ($question->getType() === ExtraQuestionTypeConstants::CheckBoxQuestionType) {
                        // special case for waiver type
                        $value = $value === 'accepted' ? 'true' : 'false';
                    }
                    $answer->setValue($value);
                    $attendee->addExtraQuestionAnswer($answer);
                    Log::debug(sprintf("RegistrationIngestionService::ingestSummit added answer %s", $answer));
                }

                $ticket->setOwner($attendee);
                // set ticket status

                if (!$cancelled && !$refunded) {
                    $ticket->setPaid();
                    $order->setPaidStatus();
                }

                if ($cancelled) {
                    $ticket->setCancelled();
                }

                if ($refunded) {
                    try {
                        if ($ticket->canRefund($ticket->getFinalAmount())) {
                            $ticket->refund
                            (
                                null,
                                $ticket->getFinalAmount(),
                                null,
                                null,
                                false
                            );
                        }
                    } catch (Exception $ex) {
                        Log::warning($ex);
                    }
                }

                // force the disclaimer
                if ($summit->isRegistrationDisclaimerMandatory() && !$attendee->isDisclaimerAccepted())
                    $attendee->setDisclaimerAcceptedDate(new \DateTime('now', new \DateTimeZone('UTC')));

                $attendee->updateStatus();
                $order->addTicket($ticket);
                $ticket->generateQRCode();
                $ticket->generateHash();
                // if we have a badge feature to add ....
                $badge_feature_name = $external_attendee_profile['badge_feature'] ?? null;
                if(!is_null($badge_feature_name)){
                    Log::debug
                    (
                        sprintf
                        (
                            "RegistrationIngestionService::ingestSummit processing badge feature %s",
                            $badge_feature_name
                        )
                    );
                    $badge_feature = $summit->getFeatureTypeByName($badge_feature_name);
                    if(is_null($badge_feature)){
                        // create it
                        Log::debug
                        (
                            sprintf
                            (
                                "RegistrationIngestionService::ingestSummit badge feature %s does not exists , creating it ...",
                                $badge_feature_name
                            )
                        );
                        $badge_feature = SummitBadgeFeatureTypeFactory::build([
                            'name' => $badge_feature_name,
                            'description' => $badge_feature_name,
                        ]);

                        $summit->addFeatureType($badge_feature);
                    }

                    Log::debug
                    (
                        sprintf
                        (
                            "RegistrationIngestionService::ingestSummit assigning badge feature %s to ticket external id %s owner %s",
                            $badge_feature_name,
                            $ticket->getExternalAttendeeId(),
                            $ticket->getOwnerEmail()
                        )
                    );

                    $ticket->getBadge()->addFeature($badge_feature);
                }
                Log::debug(sprintf("RegistrationIngestionService::ingestSummit processed attendee %s", $external_attendee['id']));

                return $attendee;
            });
        } catch (Exception $ex) {
            Log::warning($ex);
        }
    }

    public function ingestAllSummits(): void
    {

        $summits = $this->tx_service->transaction(function () {
            return $this->summit_repository->getAllWithExternalRegistrationFeed();
        });

        foreach ($summits as $summit) {
            $this->ingestSummit($summit);
        }
    }

    /**
     * @param Summit $summit
     * @throws \Exception
     */
    public function ingestSummit(Summit $summit): void
    {
        try {
            $start = time();
            $summit_id = $summit->getId();
            $page = 1;
            $has_more_items = false;
            Log::debug(sprintf("RegistrationIngestionService::ingestSummit ingesting summit %s", $summit_id));
            $feed = $this->feed_factory->build($summit);

            if (is_null($feed))
                throw new ValidationException("invalid feed");

            if (!$summit->hasDefaultBadgeType())
                throw new ValidationException(sprintf("summit %s has not default badge type set", $summit_id));

            $shouldMarkProcess = false;
            do {
                Log::debug(sprintf("RegistrationIngestionService::ingestSummit getting external attendees page %s", $page));
                $response = $feed->getAttendees($page, $summit->getExternalRegistrationFeedLastIngestDate());

                if ($response->hasData()) {
                    $shouldMarkProcess = true;
                } else {
                    log::debug
                    (
                        sprintf
                        (
                            "RegistrationIngestionService::ingestSummit page does not contains data for summit %s"
                            , $summit_id
                        )
                    );
                    break;
                }

                $has_more_items = $response->hasMoreItems();

                Log::debug(sprintf("RegistrationIngestionService::ingestSummit response got %s records on page", $response->pageCount()));
                Log::debug(sprintf("RegistrationIngestionService::ingestSummit response %s", $response->__toString()));

                foreach ($response as $index => $external_attendee) {
                    $this->ingestExternalAttendee($summit_id, $index, $external_attendee);
                }

                ++$page;
            } while ($has_more_items);

            $this->tx_service->transaction(function () use ($summit_id, $shouldMarkProcess) {
                if ($shouldMarkProcess) {
                    log::debug
                    (
                        sprintf
                        (
                            "RegistrationIngestionService::ingestSummit marking last ingest date for summit %s"
                            , $summit_id
                        )
                    );
                    $summit = $this->summit_repository->getById($summit_id);
                    $summit->markExternalRegistrationFeedLastIngestDate();
                }
            });

            $end = time();
            $delta = $end - $start;
            log::debug
            (
                sprintf
                (
                    "RegistrationIngestionService::ingestSummit execution call %s seconds - summit %s"
                    , $delta
                    , $summit_id
                )
            );
        } catch (Exception $ex) {
            Log::warning(sprintf("error external feed for summit id %s", $summit->getId()));
            Log::warning($ex);
        }
    }
}