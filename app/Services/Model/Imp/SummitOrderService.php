<?php namespace App\Services\Model;
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

use App\Events\CreatedSummitRegistrationOrder;
use App\Events\MemberUpdated;
use App\Events\TicketUpdated;
use App\Facades\ResourceServerContext;
use App\Http\Renderers\SummitAttendeeTicketPDFRenderer;
use App\Jobs\CopyInvitationTagsToAttendees;
use App\Jobs\Emails\RegisteredMemberOrderPaidMail;
use App\Jobs\Emails\Registration\Reminders\SummitOrderReminderEmail;
use App\Jobs\Emails\Registration\Reminders\SummitTicketReminderEmail;
use App\Jobs\Emails\UnregisteredMemberOrderPaidMail;
use App\Jobs\IngestSummitExternalRegistrationData;
use App\Jobs\ProcessTicketDataImport;
use App\Jobs\SendAttendeeInvitationEmail;
use App\Models\Foundation\Summit\Factories\SummitOrderFactory;
use App\Models\Foundation\Summit\Registration\IBuildDefaultPaymentGatewayProfileStrategy;
use App\Models\Foundation\Summit\Repositories\ISummitAttendeeBadgePrintRuleRepository;
use App\Models\Foundation\Summit\Repositories\ISummitAttendeeBadgeRepository;
use App\Models\Foundation\Summit\Repositories\ISummitOrderRepository;
use App\Services\FileSystem\IFileDownloadStrategy;
use App\Services\FileSystem\IFileUploadStrategy;
use App\Services\Model\dto\ExternalUserDTO;
use App\Services\Model\Strategies\TicketFinder\ITicketFinderStrategyFactory;
use App\Services\Utils\CSVReader;
use App\Services\Utils\ILockManagerService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use libs\utils\ITransactionService;
use libs\utils\TextUtils;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use models\main\ICompanyRepository;
use models\main\IMemberRepository;
use models\main\ITagRepository;
use models\main\Member;
use models\oauth2\IResourceServerContext;
use models\summit\factories\SummitAttendeeFactory;
use models\summit\IPaymentConstants;
use models\summit\ISummitAttendeeRepository;
use models\summit\ISummitAttendeeTicketRepository;
use models\summit\ISummitRegistrationPromoCodeRepository;
use models\summit\ISummitRepository;
use models\summit\ISummitTicketTypeRepository;
use models\summit\PrePaidSummitRegistrationDiscountCode;
use models\summit\PrePaidSummitRegistrationPromoCode;
use models\summit\Summit;
use models\summit\SummitAccessLevelType;
use models\summit\SummitAttendee;
use models\summit\SummitAttendeeBadge;
use models\summit\SummitAttendeeTicket;
use models\summit\SummitBadgeType;
use models\summit\SummitBadgeViewType;
use models\summit\SummitOrder;
use models\summit\SummitOrderExtraQuestionTypeConstants;
use models\summit\SummitRegistrationInvitation;
use models\summit\SummitRegistrationPromoCode;
use models\summit\SummitTicketType;
use utils\PagingInfo;

/**
 * Class AbstractTask
 * @package App\Services\Model
 */
abstract class AbstractTask
{

    public abstract function run(array $formerState): array;

    public abstract function undo();
}

/**
 * Class Saga
 * @package App\Services\Model
 */
final class Saga
{

    private function __construct()
    {
    }

    /**
     * @var AbstractTask[]
     */
    private $tasks = [];
    /**
     * @var AbstractTask[]
     */
    private $already_run_tasks = [];

    public static function start(): Saga
    {
        return new Saga();
    }

    public function addTask(AbstractTask $task): Saga
    {
        $this->tasks[] = $task;
        return $this;
    }

    private function markAsRan(AbstractTask $task)
    {
        $this->already_run_tasks[] = $task;
    }


    private function abort()
    {
        foreach (array_reverse($this->already_run_tasks) as $task) {
            $task->undo();
        }
    }

    /**
     * @throws \Exception
     */
    public function run(): array
    {
        try {
            $formerState = [];
            foreach ($this->tasks as $task) {
                $formerState = $task->run($formerState);
                $this->markAsRan($task);
            }
            return $formerState;
        } catch (\Exception $ex) {
            Log::warning($ex);
            $this->abort();
            throw $ex;
        }
    }
}

final class SagaFactory {

    /**
     * @var IMemberRepository
     */
    private $member_repository;

    /**
     * @var ISummitTicketTypeRepository
     */
    private $ticket_type_repository;

    /**
     * @var ISummitRegistrationPromoCodeRepository
     */
    private $promo_code_repository;

    /**
     * @var ISummitAttendeeRepository
     */
    private $attendee_repository;

    /**
     * @var ISummitAttendeeTicketRepository
     */
    private $ticket_repository;

    /**
     * @var IBuildDefaultPaymentGatewayProfileStrategy
     */
    private $default_payment_gateway_strategy;

    /**
     * @var ILockManagerService
     */
    private $lock_service;

    /**
     * @var ICompanyService
     */
    private $company_service;

    /**
     * @var ICompanyRepository
     */
    private $company_repository;

    /**
     * @var ITransactionService
     */
    protected $tx_service;

    /**
     * @param IMemberRepository $member_repository
     * @param ISummitTicketTypeRepository $ticket_type_repository
     * @param ISummitRegistrationPromoCodeRepository $promo_code_repository
     * @param ISummitAttendeeRepository $attendee_repository
     * @param ISummitAttendeeTicketRepository $ticket_repository
     * @param IBuildDefaultPaymentGatewayProfileStrategy $default_payment_gateway_strategy
     * @param ILockManagerService $lock_service
     * @param ICompanyService $company_service
     * @param ICompanyRepository $company_repository
     * @param ITransactionService $tx_service
     */
    public function __construct(
        IMemberRepository $member_repository,
        ISummitTicketTypeRepository $ticket_type_repository,
        ISummitRegistrationPromoCodeRepository $promo_code_repository,
        ISummitAttendeeRepository $attendee_repository,
        ISummitAttendeeTicketRepository $ticket_repository,
        IBuildDefaultPaymentGatewayProfileStrategy $default_payment_gateway_strategy,
        ILockManagerService $lock_service,
        ICompanyService $company_service,
        ICompanyRepository $company_repository,
        ITransactionService $tx_service)
    {
        $this->member_repository = $member_repository;
        $this->ticket_type_repository = $ticket_type_repository;
        $this->promo_code_repository = $promo_code_repository;
        $this->attendee_repository = $attendee_repository;
        $this->ticket_repository = $ticket_repository;
        $this->default_payment_gateway_strategy = $default_payment_gateway_strategy;
        $this->lock_service = $lock_service;
        $this->company_service = $company_service;
        $this->company_repository = $company_repository;
        $this->tx_service = $tx_service;
    }

    private function buildRegularSaga(Member $owner, Summit $summit, array $payload): Saga {
        Log::debug(sprintf("SagaFactory::buildRegularSaga - summit id %s", $summit->getId()));
        return Saga::start()
            ->addTask(new PreOrderValidationTask($summit, $payload, $this->ticket_type_repository, $this->tx_service))
            ->addTask(new PreProcessReservationTask($payload))
            ->addTask(new ReserveTicketsTask($summit, $this->ticket_type_repository, $this->tx_service, $this->lock_service))
            ->addTask(new ApplyPromoCodeTask($summit, $payload, $this->promo_code_repository, $this->tx_service, $this->lock_service))
            ->addTask(new ReserveOrderTask(
                $owner,
                $summit,
                $payload,
                $this->default_payment_gateway_strategy,
                $this->member_repository,
                $this->attendee_repository,
                $this->ticket_repository,
                $this->company_repository,
                $this->company_service,
                $this->tx_service
            ));
    }

    private function buildPrePaidSaga(Member $owner, Summit $summit, array $payload): Saga {
        Log::debug(sprintf("SagaFactory::buildPrePaidSaga - summit id %s", $summit->getId()));
        return Saga::start()
            ->addTask(new PreOrderValidationTask($summit, $payload, $this->ticket_type_repository, $this->tx_service))
            ->addTask(new PreProcessReservationTask($payload))
            ->addTask(new AutoAssignPrePaidTicketTask(
                $owner,
                $summit,
                $payload,
                $this->member_repository,
                $this->attendee_repository,
                $this->ticket_type_repository,
                $this->tx_service,
                $this->lock_service
            ));
    }

    public function build(Member $owner, Summit $summit, array $payload): Saga {
        Log::debug(sprintf("SagaFactory::build - summit id %s", $summit->getId()));

        $tickets = $payload['tickets'];

        //Precondition: for PrePaid case, only one ticket at a time
        if(count($tickets) === 1) {
            $ticket_dto = $tickets[0];
            $promo_code_val = $ticket_dto['promo_code'] ?? null;
            if(!empty($promo_code_val)) {
                // get promo code
                $promo_code = $summit->getPromoCodeByCode($promo_code_val);
                Log::debug(sprintf("SagaFactory::build - summit id %s, promo_code %s", $summit->getId(), $promo_code_val));

                if ($promo_code instanceof PrePaidSummitRegistrationDiscountCode ||
                    $promo_code instanceof PrePaidSummitRegistrationPromoCode) {
                    return $this->buildPrePaidSaga($owner, $summit, $payload);
                }
            }
        }
        return $this->buildRegularSaga($owner, $summit, $payload);
    }
}

/**
 * Class TaskUtils
 * @package App\Services\Model
 */
final class TaskUtils
{
    public static function getOwnerCompanyName($summit, $payload): ?string
    {
        $owner_company_id = $payload['owner_company_id'] ?? null;
        if (!is_null($owner_company_id)) {
            $company = $summit->getRegistrationCompanyById(intval($owner_company_id));
            return !is_null($company) ? $company->getName() : null;
        }
        return $payload['owner_company'] ?? null;
    }
}

/**
 * Class ReserveOrderTask
 * @package App\Services\Model
 */
final class ReserveOrderTask extends AbstractTask
{
    /**
     * @var ITransactionService
     */
    private $tx_service;

    /**
     * @var Summit
     */
    private $summit;

    /**
     * @var array
     */
    private $formerState;

    /**
     * @var array
     */
    private $payload;

    /**
     * @var IMemberRepository
     */
    private $member_repository;

    /**
     * @var ISummitAttendeeRepository
     */
    private $attendee_repository;

    /**
     * @var ISummitAttendeeTicketRepository
     */
    private $ticket_repository;

    /**
     * @var Member
     */
    private $owner;

    /**
     * @var IBuildDefaultPaymentGatewayProfileStrategy
     */
    private $default_payment_gateway_strategy;

    /**
     * @var ICompanyService
     */
    private $company_service;

    /**
     * @var ICompanyRepository
     */
    private $company_repository;

    /**
     * @param Member|null $owner
     * @param Summit $summit
     * @param array $payload
     * @param IBuildDefaultPaymentGatewayProfileStrategy $default_payment_gateway_strategy
     * @param IMemberRepository $member_repository
     * @param ISummitAttendeeRepository $attendee_repository
     * @param ISummitAttendeeTicketRepository $ticket_repository
     * @param ICompanyRepository $company_repository
     * @param ICompanyService $company_service
     * @param ITransactionService $tx_service
     */
    public function __construct
    (
        ?Member                                    $owner,
        Summit                                     $summit,
        array                                      $payload,
        IBuildDefaultPaymentGatewayProfileStrategy $default_payment_gateway_strategy,
        IMemberRepository                          $member_repository,
        ISummitAttendeeRepository                  $attendee_repository,
        ISummitAttendeeTicketRepository            $ticket_repository,
        ICompanyRepository                         $company_repository,
        ICompanyService                            $company_service,
        ITransactionService                        $tx_service
    )
    {

        $this->tx_service = $tx_service;
        $this->summit = $summit;
        $this->payload = $payload;
        $this->member_repository = $member_repository;
        $this->attendee_repository = $attendee_repository;
        $this->ticket_repository = $ticket_repository;
        $this->default_payment_gateway_strategy = $default_payment_gateway_strategy;
        $this->owner = $owner;
        $this->company_service = $company_service;
        $this->company_repository = $company_repository;
    }

    public function run(array $formerState): array
    {
        $this->formerState = $formerState;

        return $this->tx_service->transaction(function () {

            $owner_email = $this->payload['owner_email'];
            $owner_first_name = $this->payload['owner_first_name'];
            $owner_last_name = $this->payload['owner_last_name'];

            Log::debug(sprintf("ReserveOrderTask::run payload %s", json_encode($this->payload)));

            $owner_company_name = TaskUtils::getOwnerCompanyName($this->summit, $this->payload);
            $tickets = $this->payload['tickets'];

            if (!is_null($this->owner) && strtolower($this->owner->getEmail()) != strtolower($owner_email)) {
                throw new ValidationException(sprintf("Owner email differs from logged user email."));
            }

            // auto assign should only happen when the user has not paid any order and the order has more than one ticket....
            $shouldAutoAssignFirstTicket = !$this->owner->hasPaidRegistrationOrderForSummit($this->summit) && count($tickets) > 1;

            // try to get invitation
            $invitation = $this->summit->getSummitRegistrationInvitationByEmail($owner_email);
            $hasPendingInvitation = !is_null($invitation) && $invitation->isPending();

            $payment_gateway = $this->summit->getPaymentGateWayPerApp
            (
                IPaymentConstants::ApplicationTypeRegistration,
                $this->default_payment_gateway_strategy
            );

            if (is_null($payment_gateway)) {
                throw new ValidationException
                (
                    sprintf
                    (
                        "Payment configuration is not set for summit %s",
                        $this->summit->getId()
                    )
                );
            }

            Log::info
            (
                sprintf
                (
                    "ReserveOrderTask::run - email %s first_name %s last_name %s company %s should_auto_assign_first_ticket %b",
                    $owner_email,
                    $owner_first_name,
                    $owner_last_name,
                    $owner_company_name,
                    $shouldAutoAssignFirstTicket
                )
            );

            $order = SummitOrderFactory::build($this->summit, $this->payload);

            $order->generateNumber();

            do {
                if (!$this->summit->existOrderNumber($order->getNumber()))
                    break;
                $order->generateNumber();
            } while (1);

            $default_badge_type = $this->summit->getDefaultBadgeType();
            // local tx attendees storage
            $local_attendees = [];
            $index = 0;
            // tickets

            foreach ($tickets as $ticket_dto) {

                Log::debug(sprintf("ReserveOrderTask::run Processing ticket #%s payload %s", $index, json_encode($ticket_dto)));
                if (!isset($ticket_dto['type_id']))
                    throw new ValidationException('type_id is mandatory');

                $type_id = $ticket_dto['type_id'];
                $ticket_type = $this->summit->getTicketTypeById(intval($type_id));

                if (is_null($ticket_type)) {
                    throw new EntityNotFoundException('Ticket type not found.');
                }

                // if the ticket type is an invitation one, it needs always be auto-assigned
                $shouldAutoAssignInvitationTicket = $hasPendingInvitation &&  $invitation->isTicketTypeAllowed($type_id);

                $promo_code_value = $ticket_dto['promo_code'] ?? null;
                // attendee data
                if (($index === 0 && $shouldAutoAssignFirstTicket) || $shouldAutoAssignInvitationTicket) {

                    Log::debug
                    (
                        sprintf
                        (
                            "ReserveOrderTask::run auto assign ticket index %s shouldAutoAssignFirstTicket %b shouldAutoAssignInvitationTicket %b",
                            $index,
                            $shouldAutoAssignFirstTicket,
                            $shouldAutoAssignInvitationTicket
                        )
                    );

                    $attendee_first_name = $this->owner->getFirstName();
                    $attendee_last_name = $this->owner->getLastName();
                    $attendee_email = $this->owner->getEmail();
                    $attendee_company = $this->owner->getCompany();

                } else {
                    // use what we have on payload
                    $attendee_first_name = $ticket_dto['attendee_first_name'] ?? null;
                    $attendee_last_name = $ticket_dto['attendee_last_name'] ?? null;
                    $attendee_email = $ticket_dto['attendee_email'] ?? null;
                    $attendee_company = $ticket_dto['attendee_company'] ?? null;
                }

                // if attendee is order owner , and company is null , set the company order
                if (!empty($attendee_email) && $attendee_email == $owner_email) {
                    if (empty($attendee_company))
                        $attendee_company = $owner_company_name;
                    if (empty($attendee_first_name))
                        $attendee_first_name = $owner_first_name;
                    if (empty($attendee_last_name))
                        $attendee_last_name = $owner_last_name;
                }

                $ticket = new SummitAttendeeTicket();
                $ticket->setOrder($order);
                $ticket->generateNumber();

                do {

                    if (!$this->ticket_repository->existNumber($ticket->getNumber()))
                        break;
                    $ticket->generateNumber();
                } while (1);

                $ticket->setTicketType($ticket_type);

                if (!$ticket->hasBadge()) {
                    $ticket->setBadge(SummitBadgeType::buildBadgeFromType($default_badge_type));
                }

                $promo_code = !empty($promo_code_value) ? $this->summit->getPromoCodeByCode($promo_code_value) : null;
                if (!is_null($promo_code)) {
                    $promo_code->applyTo($ticket);
                }

                $ticket->applyTaxes($this->summit->getTaxTypes()->toArray());

                if (!empty($attendee_email)) {

                    $attendee_email = strtolower(trim($attendee_email));
                    Log::debug(sprintf("ReserveOrderTask::run - processing attendee_email %s", $attendee_email));
                    // assign attendee
                    // check if we have already an attendee on this summit
                    $attendee = $this->attendee_repository->getBySummitAndEmail($this->summit, $attendee_email);
                    // check on local reservation

                    if (is_null($attendee) && isset($local_attendees[$attendee_email])) {
                        Log::debug(sprintf("ReserveOrderTask::run - attendee_email %s not fund in repo getting it from local tx", $attendee_email));
                        $attendee = $local_attendees[$attendee_email];
                    }

                    $attendee_owner = $this->owner->getEmail() === $attendee_email ? $this->owner : $this->member_repository->getByEmail($attendee_email);

                    if (is_null($attendee)) {

                        Log::debug(sprintf("ReserveOrderTask::run - creating attendee %s for summit %s", $attendee_email, $this->summit->getId()));
                        $attendee = SummitAttendeeFactory::build($this->summit, [
                            'first_name' => $attendee_first_name,
                            'last_name' => $attendee_last_name,
                            'email' => $attendee_email,
                            'company' => $attendee_company
                        ], $attendee_owner);
                    }

                    $attendee = SummitAttendeeFactory::populate
                    (
                        $this->summit,
                        $attendee,
                        [
                            'first_name' => $attendee_first_name,
                            'last_name' => $attendee_last_name,
                            'email' => $attendee_email,
                            'company' => $attendee_company
                        ],
                        $attendee_owner
                    );
                    $attendee->updateStatus();
                    $local_attendees[$attendee_email] = $attendee;
                    $ticket->setOwner($attendee);
                }

                $order->addTicket($ticket);
                $ticket->generateQRCode();
                $ticket->generateHash();
                ++$index;
            }

            if (is_null($this->owner)) {
                Log::debug(sprintf("ReserveOrderTask::run is null trying to get owner by email %s", $owner_email));
                $this->owner = $this->member_repository->getByEmail($owner_email);
            }

            if (!is_null($this->owner)) {
                Log::debug
                (
                    sprintf
                    (
                        "ReserveOrderTask::run owner is set to owner id %s %s %s",
                        $this->owner->getId(),
                        $this->owner->getFirstName(),
                        $this->owner->getLastName()
                    )
                );
                $this->owner->addSummitRegistrationOrder($order);
            }

            $this->summit->addOrder($order);
            // generate payment if cost > 0
            if ($order->getFinalAmount() > 0) {
                $payment_gateway->preProcessOrder($order);
            }

            // generate the key to access
            $order->generateHash();
            $order->generateQRCode();
            if($hasPendingInvitation){
                // add the order to the corresponding invitation , if does exist to avoid user
                // to purchase multiple tickets for the same invitation
                Log::debug
                (
                    sprintf
                    (
                        "ReserveOrderTask::run has a pending invitation %s for email %s",
                        $invitation->getId(),
                        $order->getOwnerEmail()
                    )
                );
                $invitation->addOrder($order);
            }
            Event::dispatch(new CreatedSummitRegistrationOrder($order->getId()));
            return ['order' => $order];
        });
    }

    public function undo()
    {
        // TODO: Implement undo() method.
    }
}

/**
 * Class ApplyPromoCodeTask
 * @package App\Services\Model
 */
final class ApplyPromoCodeTask extends AbstractTask
{

    /**
     * @var ITransactionService
     */
    private $tx_service;

    /**
     * @var Summit
     */
    private $summit;

    /**
     * @var array
     */
    private $formerState;

    /**
     * @var array
     */
    private $payload;

    /**
     * @var ISummitRegistrationPromoCodeRepository
     */
    private $promo_code_repository;

    /**
     * @var ILockManagerService
     */
    private $lock_service;

    /**
     * ApplyPromoCodeTask constructor.
     * @param Summit $summit
     * @param array $payload
     * @param ISummitRegistrationPromoCodeRepository $promo_code_repository
     * @param ITransactionService $tx_service
     * @param ILockManagerService $lock_service
     */
    public function __construct
    (
        Summit                                 $summit,
        array                                  $payload,
        ISummitRegistrationPromoCodeRepository $promo_code_repository,
        ITransactionService                    $tx_service,
        ILockManagerService                    $lock_service
    )
    {
        $this->tx_service = $tx_service;
        $this->summit = $summit;
        $this->payload = $payload;
        $this->promo_code_repository = $promo_code_repository;
        $this->lock_service = $lock_service;
    }

    /**
     * @param array $formerState
     * @return array
     * @throws \Exception
     */
    public function run(array $formerState): array
    {
        $this->formerState = $formerState;
        $promo_codes_usage = $this->formerState['promo_codes_usage'];
        $owner_email = $this->payload['owner_email'];
        $owner_company_name = TaskUtils::getOwnerCompanyName($this->summit, $this->payload);

        foreach ($promo_codes_usage as $promo_code_value => $info) {

            $this->tx_service->transaction(function () use ($owner_email, $owner_company_name, $promo_code_value, $info) {

                $promo_code = $this->promo_code_repository->getByValueExclusiveLock($this->summit, $promo_code_value);

                if (is_null($promo_code) || !$promo_code instanceof SummitRegistrationPromoCode) {
                    throw new EntityNotFoundException(sprintf('The Promo Code “%s” is not a valid code.', $promo_code_value));
                }

                if ($promo_code->getSummitId() != $this->summit->getId()) {
                    throw new EntityNotFoundException(sprintf("Promo Code %s not found on summit %s.", $promo_code->getCode(), $this->summit->getId()));
                }

                $qty = intval($info['qty']);

                $promo_code->validate($owner_email, $owner_company_name);

                foreach ($info['types'] as $ticket_type_id) {
                    $ticket_type = $this->summit->getTicketTypeById($ticket_type_id);
                    if (is_null($ticket_type)) {
                        throw new ValidationException(sprintf("Ticket Type %s not found on summit %s.", $ticket_type_id, $this->summit->getId()));
                    }
                    if (!$promo_code->canBeAppliedTo($ticket_type)) {
                        Log::debug(sprintf("Promo code %s can not be applied to ticket type %s", $promo_code->getCode(), $ticket_type->getName()));
                        throw new ValidationException(sprintf("Promo code %s can not be applied to Ticket Type %s.", $promo_code->getCode(), $ticket_type->getName()));
                    }
                }

                Log::debug(sprintf("adding %s usage to promo code %s", $qty, $promo_code->getId()));

                $this->lock_service->lock('promocode.' . $promo_code->getId() . '.usage.lock', function () use ($promo_code, $qty, $owner_email) {
                    $promo_code->addUsage($owner_email, $qty);
                });

            });
            // mark a done
            $promo_codes_usage[$promo_code_value]['redeem'] = true;
        }
        // update state
        $this->formerState['promo_codes_usage'] = $promo_codes_usage;

        return $this->formerState;
    }

    public function undo()
    {
        Log::debug
        (
            sprintf
            (
                "ApplyPromoCodeTask::undo: compensating transaction former state %s payload %s",
                json_encode($this->formerState),
                json_encode($this->payload)
            )
        );

        $promo_codes_usage = $this->formerState['promo_codes_usage'];
        $owner_email = $this->payload['owner_email'];

        foreach ($promo_codes_usage as $code => $info)
        {
            Log::debug(sprintf("ApplyPromoCodeTask::undo undoing promo code %s info %s owner_email %s", $code, json_encode($info), $owner_email));

            $this->tx_service->transaction(function () use ($code, $info, $owner_email) {
                $promo_code = $this->promo_code_repository->getByValueExclusiveLock($this->summit, $code);
                if (is_null($promo_code)) return;

                if (!isset($info['redeem'])) return;

                $this->lock_service->lock('promocode.' . $promo_code->getId() . '.usage.lock', function () use ($promo_code, $info, $owner_email) {
                    $promo_code->removeUsage(intval($info['qty']), $owner_email);
                });

            });
        }
    }
}

/**
 * Class ReserveTicketsTask
 * @package App\Services\Model
 */
final class ReserveTicketsTask extends AbstractTask
{

    /**
     * @var ITransactionService
     */
    private $tx_service;

    /**
     * @var Summit
     */
    private $summit;

    /**
     * @var array
     */
    private $formerState;

    /**
     * @var ISummitTicketTypeRepository
     */
    private $ticket_type_repository;

    /**
     * @var ILockManagerService
     */
    private $lock_service;

    /**
     * ReserveTicketsTask constructor.
     * @param Summit $summit
     * @param ISummitTicketTypeRepository $ticket_type_repository
     * @param ITransactionService $tx_service
     */
    public function __construct
    (
        Summit                      $summit,
        ISummitTicketTypeRepository $ticket_type_repository,
        ITransactionService         $tx_service,
        ILockManagerService         $lock_service
    )
    {
        $this->tx_service = $tx_service;
        $this->lock_service = $lock_service;
        $this->summit = $summit;
        $this->ticket_type_repository = $ticket_type_repository;
    }

    public function run(array $formerState): array
    {
        $this->formerState = $formerState;
        // reserve all tix on a tx ( all or nothing)
        $this->tx_service->transaction(function () {
            $ticket_types_ids = $this->formerState['ticket_types_ids'];
            $reservations = $this->formerState['reservations'];
            $ticket_types = $this->ticket_type_repository->getByIdsExclusiveLock($this->summit, $ticket_types_ids);
            $former_currency = null;

            foreach ($ticket_types as $ticket_type) {

                if (!empty($former_currency) && $ticket_type->getCurrency() != $former_currency) {
                    throw new ValidationException("order should have tickets with same currency");
                }

                $former_currency = $ticket_type->getCurrency();
                if (!$ticket_type instanceof SummitTicketType) {
                    throw new EntityNotFoundException("ticket type not found");
                }
                if (!$ticket_type->canSell()) {
                    throw new ValidationException(sprintf('The ticket “%s” is not available. Please go back and select a different ticket.', $ticket_type->getName()));
                }

                $this->lock_service->lock('ticket_type.' . $ticket_type->getId() . '.sell.lock', function () use ($ticket_type, $reservations) {
                    $ticket_type->sell($reservations[$ticket_type->getId()]);
                });

            }
        });
        return $formerState;
    }

    public function undo()
    {
        Log::info("ReserveTicketsTask::undo: compensating transaction");
        $reservations = $this->formerState['reservations'];
        foreach ($reservations as $ticket_id => $qty) {
            $this->tx_service->transaction(function () use ($ticket_id, $qty) {
                $ticket_type = $this->ticket_type_repository->getByIdExclusiveLock($ticket_id);
                if (is_null($ticket_type)) return;
                $this->lock_service->lock('ticket_type.' . $ticket_type->getId() . '.sell.lock', function () use ($ticket_type, $qty) {
                    $ticket_type->restore($qty);
                });
            });
        }
    }
}

/**
 * Class PreProcessReservationTask
 * @package App\Services\Model
 */
final class PreProcessReservationTask extends AbstractTask
{

    /**
     * @var array
     */
    private $payload;

    /**
     * PreProcessReservationTask constructor.
     * @param array $payload
     */
    public function __construct(array $payload)
    {
        $this->payload = $payload;
    }

    /**
     * @param array $formerState
     * @return array
     */
    public function run(array $formerState): array
    {
        $reservations = [];
        $promo_codes_usage = [];
        $ticket_types_ids = [];

        // sum reservations by tix types to check availability
        $tickets = $this->payload['tickets'];

        foreach ($tickets as $ticket_dto) {
            if (!isset($ticket_dto['type_id']))
                throw new ValidationException('type_id is mandatory.');

            $type_id = intval($ticket_dto['type_id']);

            if (!in_array($type_id, $ticket_types_ids))
                $ticket_types_ids[] = $type_id;

            $promo_code_value = isset($ticket_dto['promo_code']) ? strtoupper(trim($ticket_dto['promo_code'])) : null;

            if (!isset($reservations[$type_id]))
                $reservations[$type_id] = 0;

            $reservations[$type_id] = $reservations[$type_id] + 1;

            if (!empty($promo_code_value)) {

                if (!isset($promo_codes_usage[$promo_code_value])) {
                    $promo_codes_usage[$promo_code_value] = [
                        'qty' => 0,
                        'types' => [],
                    ];
                }

                $info = $promo_codes_usage[$promo_code_value];
                $info['qty'] = $info['qty'] + 1;

                if (!in_array($type_id, $info['types']))
                    $info['types'] = array_merge($info['types'], [$type_id]);

                $promo_codes_usage[$promo_code_value] = $info;
            }
        }
        return [
            "reservations" => $reservations,
            "promo_codes_usage" => $promo_codes_usage,
            "ticket_types_ids" => $ticket_types_ids,
        ];
    }

    public function undo()
    {
        // TODO: Implement undo() method.
    }
}

/**
 * Class PreOrderValidationTask
 * @package App\Services\Model
 */
final class PreOrderValidationTask extends AbstractTask
{
    /**
     * @var ITransactionService
     */
    private $tx_service;

    /**
     * @var Summit
     */
    private $summit;

    /**
     * @var array
     */
    private $payload;

    /**
     * @var ISummitTicketTypeRepository
     */
    private $ticket_type_repository;

    /**
     * @param Summit $summit
     * @param array $payload
     * @param ISummitTicketTypeRepository $ticket_type_repository
     * @param ITransactionService $tx_service
     */
    public function __construct
    (
        Summit                      $summit, array $payload,
        ISummitTicketTypeRepository $ticket_type_repository,
        ITransactionService         $tx_service
    )
    {
        $this->tx_service = $tx_service;
        $this->summit = $summit;
        $this->payload = $payload;
        $this->ticket_type_repository = $ticket_type_repository;
    }

    public function run(array $formerState): array
    {
        // pre checks
        $this->tx_service->transaction(function () {
            $extra_questions = isset($this->payload['extra_questions']) ? $this->payload['extra_questions'] : [];
            // check if we have at least a default badge template
            if (!$this->summit->hasDefaultBadgeType())
                throw new ValidationException(sprintf("Summit %s has not default badge type set", $this->summit->getId()));
            // check if we are on registration period
            if (!$this->summit->isRegistrationPeriodOpen())
                throw new ValidationException(sprintf("Summit %s registration period is closed", $this->summit->getId()));
            $owner_email = $this->payload['owner_email'];

            // check extra question for order ( if they exists and if they are mandatory)

            $mandatory_per_order = $this->summit->getMandatoryOrderExtraQuestionsByUsage(SummitOrderExtraQuestionTypeConstants::OrderQuestionUsage);

            if ($mandatory_per_order->count() != count($extra_questions)) {
                throw new ValidationException("extra_questions is mandatory");
            }

            if ($mandatory_per_order->count() > 0) {
                // check if we have all mandatories filled up
                foreach ($mandatory_per_order as $question) {
                    $found = false;
                    foreach ($extra_questions as $question_answer) {
                        if ($question_answer['question_id'] == $question->getId() && !empty($question_answer['answer'])) {
                            $found = true;
                            break;
                        }
                    }

                    if (!$found) {
                        throw new ValidationException(sprintf("question %s is mandatory", $question->getId()));
                    }
                }
            }

            // check if we are allowed to buy ticket by type
            $tickets = $this->payload['tickets'];

            // create the reservation excerpt
            $reservations = [];
            foreach ($tickets as $ticket_dto) {
                if (!isset($ticket_dto['type_id']))
                    throw new ValidationException('type_id is mandatory');
                $type_id = intval($ticket_dto['type_id']);
                if (!isset($reservations[$type_id]))
                    $reservations[$type_id] = 0;
                $reservations[$type_id] += 1;
            }

            foreach ($reservations as $type_id => $qty) {

                $ticket_type = $this->ticket_type_repository->getById($type_id);
                if (is_null($ticket_type) || !$ticket_type instanceof SummitTicketType)
                    throw new EntityNotFoundException(sprintf("Ticket Type %s not found.", $type_id));

                if (!$this->summit->canBuyRegistrationTicketByType($owner_email, $ticket_type)) {
                    throw new ValidationException
                    (
                        sprintf
                        (
                            "Email %s can not buy registration tickets of type %s for summit %s.",
                            $owner_email,
                            $ticket_type->getName(),
                            $this->summit->getName()
                        )
                    );
                }
            }
        });
        return [];
    }

    public function undo()
    {
        // TODO: Implement undo() method.
    }
}

/**
 * Class AutoAssignPrePaidTicketTask
 * @package App\Services\Model
 */
final class AutoAssignPrePaidTicketTask extends AbstractTask
{

    /**
     * @var ITransactionService
     */
    private $tx_service;

    /**
     * @var Member
     */
    private $owner;

    /**
     * @var Summit
     */
    private $summit;

    /**
     * @var array
     */
    private $formerState;

    /**
     * @var array
     */
    private $payload;

    /**
     * @var IMemberRepository
     */
    private $member_repository;

    /**
     * @var ISummitAttendeeRepository
     */
    private $attendee_repository;

    /**
     * @var ISummitTicketTypeRepository
     */
    private $ticket_type_repository;

    /**
     * @var ILockManagerService
     */
    private $lock_service;

    /**
     * AutoAssignPrePaidTicketTask constructor.
     * @param Member|null $owner
     * @param Summit $summit
     * @param array $payload
     * @param IMemberRepository $member_repository
     * @param ISummitAttendeeRepository $attendee_repository
     * @param ISummitTicketTypeRepository $ticket_type_repository
     * @param ITransactionService $tx_service
     * @param ILockManagerService $lock_service
     */
    public function __construct
    (
        ?Member $owner,
        Summit $summit,
        array $payload,
        IMemberRepository $member_repository,
        ISummitAttendeeRepository $attendee_repository,
        ISummitTicketTypeRepository $ticket_type_repository,
        ITransactionService $tx_service,
        ILockManagerService $lock_service
    )
    {
        $this->tx_service = $tx_service;
        $this->lock_service = $lock_service;
        $this->owner = $owner;
        $this->summit = $summit;
        $this->payload = $payload;
        $this->member_repository = $member_repository;
        $this->attendee_repository = $attendee_repository;
        $this->ticket_type_repository = $ticket_type_repository;
    }

    public function run(array $formerState): array
    {
        Log::debug("AutoAssignPrePaidTicketTask::run");

        $this->formerState = $formerState;

        return $this->tx_service->transaction(function () {
            if (is_null($this->owner)) throw new ValidationException("Ticket owner can't be null.");

            $tickets = $this->payload['tickets'];
            $ticket_dto = $tickets[0];

            $promo_code_val = $ticket_dto['promo_code'] ?? null;
            if (empty($promo_code_val)) throw new ValidationException("Promo code is required.");

            $type_id = $ticket_dto['type_id'];
            $order = $this->lock_service->lock('ticket_type.' . $type_id . 'promo_code.' . $promo_code_val .'.sell.lock',
                function () use ($promo_code_val, $type_id) {
                    $promo_code = $this->summit->getPromoCodeByCode($promo_code_val);
                    if (is_null($promo_code)) throw new EntityNotFoundException("Promo code is not found.");

                    $ticket_type = $this->summit->getTicketTypeById($type_id);
                    if (is_null($ticket_type)) throw new EntityNotFoundException("Ticket Type is not found.");

                    $ticket = $promo_code->getNextAvailableTicketPerType($ticket_type);
                    if (is_null($ticket))
                        throw new ValidationException(sprintf("No more available PrePaid Tickets for Promo Code %s", $promo_code->getCode()));

                    $attendee_email = $this->owner->getEmail();
                    Log::debug(sprintf("AutoAssignPrePaidTicketTask::run - processing attendee_email %s", $attendee_email));
                    // assign attendee
                    // check if we have already an attendee on this summit
                    $attendee = $this->attendee_repository->getBySummitAndEmail($this->summit, $attendee_email);

                    if (is_null($attendee)) {
                        Log::debug(sprintf("AutoAssignPrePaidTicketTask::run - creating attendee %s for summit %s", $attendee_email, $this->summit->getId()));
                        $attendee = SummitAttendeeFactory::build($this->summit, [
                            'first_name' => $this->owner->getFirstName(),
                            'last_name' => $this->owner->getLastName(),
                            'email' => $attendee_email,
                            'company' => $this->owner->getCompany()
                        ], $this->owner);
                    }
                    $attendee->updateStatus();
                    $order = $ticket->getOrder();
                    $ticket->setOwner($attendee);
                    return $order;
                });
            return ['order' => $order];
        });
    }

    public function undo() {}
}

/**
 * Class SummitOrderService
 * @package App\Services\Model
 */
final class SummitOrderService
    extends AbstractService implements ISummitOrderService
{
    /**
     * @var IMemberRepository
     */
    private $member_repository;

    /**
     * @var ISummitTicketTypeRepository
     */
    private $ticket_type_repository;

    /**
     * @var ISummitRegistrationPromoCodeRepository
     */
    private $promo_code_repository;

    /**
     * @var ISummitAttendeeRepository
     */
    private $attendee_repository;

    /**
     * @var ISummitOrderRepository
     */
    private $order_repository;

    /**
     * @var ISummitAttendeeTicketRepository
     */
    private $ticket_repository;

    /**
     * @var ISummitAttendeeBadgeRepository
     */
    private $badge_repository;

    /**
     * @var ISummitRepository
     */
    private $summit_repository;

    /**
     * @var ISummitAttendeeBadgePrintRuleRepository
     */
    private $print_rules_repository;

    /**
     * @var IMemberService
     */
    private $member_service;

    /**
     * @var IBuildDefaultPaymentGatewayProfileStrategy
     */
    private $default_payment_gateway_strategy;

    /**
     * @var IFileUploadStrategy
     */
    private $upload_strategy;

    /**
     * @var IFileDownloadStrategy
     */
    private $download_strategy;

    /**
     * @var ILockManagerService
     */
    private $lock_service;

    /**
     * @var IResourceServerContext
     */
    private $resource_ctx_service;

    /**
     * @var ICompanyService
     */
    private $company_service;

    /**
     * @var ICompanyRepository
     */
    private $company_repository;

    /**
     * @var ITicketFinderStrategyFactory
     */
    private $ticket_finder_strategy_factory;

    /**
     * @var ITagRepository
     */
    private $tags_repository;

    /**
     * @param ISummitTicketTypeRepository $ticket_type_repository
     * @param IMemberRepository $member_repository
     * @param ISummitRegistrationPromoCodeRepository $promo_code_repository
     * @param ISummitAttendeeRepository $attendee_repository
     * @param ISummitOrderRepository $order_repository
     * @param ISummitAttendeeTicketRepository $ticket_repository
     * @param ISummitAttendeeBadgeRepository $badge_repository
     * @param ISummitRepository $summit_repository
     * @param ISummitAttendeeBadgePrintRuleRepository $print_rules_repository
     * @param IMemberService $member_service
     * @param IBuildDefaultPaymentGatewayProfileStrategy $default_payment_gateway_strategy
     * @param IFileUploadStrategy $upload_strategy
     * @param IFileDownloadStrategy $download_strategy
     * @param ICompanyRepository $company_repository
     * @param ITagRepository $tags_repository
     * @param ICompanyService $company_service
     * @param ITicketFinderStrategyFactory $ticket_finder_strategy_factory
     * @param ITransactionService $tx_service
     * @param ILockManagerService $lock_service
     */
    public function __construct
    (
        ISummitTicketTypeRepository                $ticket_type_repository,
        IMemberRepository                          $member_repository,
        ISummitRegistrationPromoCodeRepository     $promo_code_repository,
        ISummitAttendeeRepository                  $attendee_repository,
        ISummitOrderRepository                     $order_repository,
        ISummitAttendeeTicketRepository            $ticket_repository,
        ISummitAttendeeBadgeRepository             $badge_repository,
        ISummitRepository                          $summit_repository,
        ISummitAttendeeBadgePrintRuleRepository    $print_rules_repository,
        IMemberService                             $member_service,
        IBuildDefaultPaymentGatewayProfileStrategy $default_payment_gateway_strategy,
        IFileUploadStrategy                        $upload_strategy,
        IFileDownloadStrategy                      $download_strategy,
        ICompanyRepository                         $company_repository,
        ITagRepository                             $tags_repository,
        ICompanyService                            $company_service,
        ITicketFinderStrategyFactory               $ticket_finder_strategy_factory,
        ITransactionService                        $tx_service,
        ILockManagerService                        $lock_service
    )
    {
        parent::__construct($tx_service);
        $this->member_repository = $member_repository;
        $this->ticket_type_repository = $ticket_type_repository;
        $this->promo_code_repository = $promo_code_repository;
        $this->attendee_repository = $attendee_repository;
        $this->order_repository = $order_repository;
        $this->ticket_repository = $ticket_repository;
        $this->badge_repository = $badge_repository;
        $this->summit_repository = $summit_repository;
        $this->print_rules_repository = $print_rules_repository;
        $this->member_service = $member_service;
        $this->default_payment_gateway_strategy = $default_payment_gateway_strategy;
        $this->upload_strategy = $upload_strategy;
        $this->download_strategy = $download_strategy;
        $this->lock_service = $lock_service;
        $this->company_repository = $company_repository;
        $this->company_service = $company_service;
        $this->ticket_finder_strategy_factory = $ticket_finder_strategy_factory;
        $this->tags_repository = $tags_repository;
    }

    /**
     * @param Member|null $owner
     * @param Summit $summit
     * @param array $payload
     * @return SummitOrder
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function reserve(?Member $owner, Summit $summit, array $payload): SummitOrder
    {

        try {
            // update owner data
            $owner = $this->tx_service->transaction(function () use ($owner, $payload) {
                if (is_null($owner)) return null;

                Log::debug(sprintf("SummitOrderService::reserve trying to get member %s", $owner->getId()));

                $owner = $this->member_repository->getByIdExclusiveLock($owner->getId());
                if (!$owner instanceof Member) return null;
                $first_name = null;
                $last_name = null;
                $company = null;
                // if we have an owner check if his name is empty amd set with what is on the payload
                if (isset($payload['owner_first_name']) && !empty($payload['owner_first_name'])) {
                    $first_name = trim($payload['owner_first_name']);
                    Log::debug(sprintf("SummitOrderService::reserve setting first name %s to member %s", $first_name, $owner->getId()));
                    $owner->setFirstName($first_name);
                }

                if (isset($payload['owner_last_name']) && !empty($payload['owner_last_name'])) {
                    $last_name = trim($payload['owner_last_name']);
                    Log::debug(sprintf("SummitOrderService::reserve setting last name %s to member %s", $last_name, $owner->getId()));
                    $owner->setLastName($last_name);
                }

                if (isset($payload['owner_company']) && !empty($payload['owner_company'])) {
                    $company = trim($payload['owner_company']);
                }

                Event::dispatch
                (
                    new MemberUpdated
                    (
                        $owner->getId(),
                        $owner->getEmail(),
                        $first_name,
                        $last_name,
                        $company
                    )
                );

                return $owner;
            });

            if (!is_null($owner) && $owner instanceof Member)
                Log::debug(sprintf("SummitOrderService::reserve owner %s %s %s", $owner->getId(), $owner->getFirstName(), $owner->getLastName()));

            $saga_factory = new SagaFactory(
                $this->member_repository,
                $this->ticket_type_repository,
                $this->promo_code_repository,
                $this->attendee_repository,
                $this->ticket_repository,
                $this->default_payment_gateway_strategy,
                $this->lock_service,
                $this->company_service,
                $this->company_repository,
                $this->tx_service);

            $state = $saga_factory->build($owner, $summit, $payload)->run();

            return $state['order'];
        } catch (ValidationException $ex) {
            Log::warning($ex);
            throw $ex;
        } catch (EntityNotFoundException $ex) {
            Log::warning($ex);
            throw $ex;
        } catch (\Exception $ex) {
            Log::error($ex);
            throw $ex;
        }
    }

    /**
     * @param Summit $summit
     * @param string $order_hash
     * @param array $payload
     * @return SummitOrder
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function checkout(Summit $summit, string $order_hash, array $payload): SummitOrder
    {
        return $this->tx_service->transaction(function () use ($summit, $order_hash, $payload) {
            $order = $this->order_repository->getByHashLockExclusive($order_hash);

            if (is_null($order) || !$order instanceof SummitOrder || $summit->getId() != $order->getSummitId())
                throw new EntityNotFoundException("order not found.");

            if ($order->isCancelled())
                throw new ValidationException("order is canceled, please retry it.");

            if ($order->isVoid())
                throw new ValidationException("order is canceled, please retry it.");

            SummitOrderFactory::populate($summit, $order, $payload);

            if ($order->isFree()) {
                // free order
                $order->setPaid();
                return $order;
            }

            // validation of zip code its only for paid events
            if (!$order->isFree() && empty($order->getBillingAddressZipCode()))
                throw new ValidationException("Zip Code is mandatory.");

            $payment_gateway = $summit->getPaymentGateWayPerApp
            (
                IPaymentConstants::ApplicationTypeRegistration,
                $this->default_payment_gateway_strategy
            );

            if (is_null($payment_gateway)) {
                throw new ValidationException
                (
                    sprintf
                    (
                        "Payment configuration is not set for summit %s.",
                        $summit->getId()
                    )
                );
            }

            return $payment_gateway->postProcessOrder($order, $payload);
        });
    }

    /**
     * @param Member $current_user
     * @param int $order_id
     * @param array $payload
     * @return SummitOrder
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function updateMyOrder(Member $current_user, int $order_id, array $payload): SummitOrder
    {
        return $this->tx_service->transaction(function () use ($current_user, $order_id, $payload) {
            $order = $this->order_repository->getByIdExclusiveLock($order_id);
            if (is_null($order) || !$order instanceof SummitOrder)
                throw new EntityNotFoundException("Order not found.");

            if (!$order->hasOwner() && $order->getOwnerEmail() == $current_user->getEmail()) {
                $current_user->addSummitRegistrationOrder($order);
            }

            if (!$order->hasOwner()) {
                throw new EntityNotFoundException("Order not found.");
            }

            if ($order->getOwner()->getId() != $current_user->getId()) {
                throw new EntityNotFoundException("Order not found.");
            }

            $summit = $order->getSummit();

            SummitOrderFactory::populate($summit, $order, $payload);

            return $order;
        });
    }

    /**
     * @param Member $current_user
     * @param int $order_id
     * @param int $ticket_id
     * @return SummitAttendeeTicket
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function revokeTicket(Member $current_user, int $order_id, int $ticket_id): SummitAttendeeTicket
    {
        return $this->tx_service->transaction(function () use ($current_user, $order_id, $ticket_id) {

            $order = $this->order_repository->getByIdExclusiveLock($order_id);

            if (!$order instanceof SummitOrder)
                throw new EntityNotFoundException("Order not found.");

            if (!$order->hasOwner() && $order->getOwnerEmail() == $current_user->getEmail()) {
                $current_user->addSummitRegistrationOrder($order);
            }

            if (!$order->hasOwner()) {
                throw new EntityNotFoundException("Order not found.");
            }

            // check that we own the order
            if ($order->getOwner()->getId() != $current_user->getId()) {
                throw new EntityNotFoundException("Order not found.");
            }

            $summit = $order->getSummit();

            if ($summit->hasReassignTicketLimit()) {
                $now = new \DateTime('now', new \DateTimeZone('UTC'));
                if ($now > $summit->getReassignTicketTillDate()) {
                    throw new ValidationException('Re-Assign ticket period closed.');
                }
            }

            $ticket = $order->getTicketById($ticket_id);

            if (is_null($ticket))
                throw new EntityNotFoundException("Ticket not found.");

            if (!$ticket->hasOwner()) {
                throw new ValidationException("You attempted to assign or reassign a ticket that you don’t have permission to assign.");
            }

            $attendee = $ticket->getOwner();

            // if ticket type audience is with invitation , then check if we can re-assigned
            if ($ticket->getTicketType()->getAudience() === SummitTicketType::Audience_With_Invitation) {
                // ticket assigned to order owner can not be reassigned if its the unique one
                if ($attendee->getEmail() === $order->getOwnerEmail()) {
                    if ($summit->getTicketCountByTypeAndOwnerEmail($ticket->getTicketType(), $attendee->getEmail()) === 1) {
                        throw new ValidationException("You can not reassign this ticket. please contact support.");
                    }
                }
            }

            if ($ticket->hasBadge() && $ticket->getBadge()->isPrinted()) {
                throw new ValidationException("Ticket can not be revoked due badge its already printed.");
            }

            $attendee->sendRevocationTicketEmail($ticket);

            $attendee->removeTicket($ticket);

            return $ticket;

        });
    }

    /**
     * @param Member $current_user
     * @param int $order_id
     * @param int $ticket_id
     * @param array $payload
     * @return SummitAttendeeTicket
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function ownerAssignTicket(Member $current_user, int $order_id, int $ticket_id, array $payload): SummitAttendeeTicket
    {
        Log::debug("SummitOrderService::ownerAssignTicket");


        return $this->tx_service->transaction(function () use ($current_user, $order_id, $ticket_id, $payload) {

            Log::debug
            (
                sprintf
                (
                    "SummitOrderService::_assignTicket order id %s ticket id %s payload %s",
                    $order_id,
                    $ticket_id,
                    json_encode($payload)
                )
            );

            // lock and get the order
            $order = $this->order_repository->getByIdExclusiveLock($order_id);

            if (!$order instanceof SummitOrder)
                throw new EntityNotFoundException("Order not found.");

            if (!$order->hasOwner() && $order->getOwnerEmail() == $current_user->getEmail()) {
                $current_user->addSummitRegistrationOrder($order);
            }

            if (!$order->hasOwner()) {
                throw new EntityNotFoundException("Order not found.");
            }

            if ($order->getOwner()->getId() != $current_user->getId()) {
                throw new EntityNotFoundException("Order not found.");
            }

            $summit = $order->getSummit();
            $ticket = $order->getTicketById($ticket_id);

            if (is_null($ticket))
                throw new EntityNotFoundException("Ticket not found.");

            if (!$ticket->isPaid())
                throw new ValidationException("Ticket is not paid.");

            // check attendee email
            $email = $payload['attendee_email'] ?? '';
            $email = TextUtils::trim($email);

            if ($ticket->hasOwner()) {

                if ($summit->hasReassignTicketLimit()) {
                    $now = new \DateTime('now', new \DateTimeZone('UTC'));
                    if ($now > $summit->getReassignTicketTillDate()) {
                        throw new ValidationException('Re-Assign ticket period closed.');
                    }
                }

                $owner = $ticket->getOwner();
                if ($owner->getEmail() != $email)
                    throw new ValidationException
                    (
                        "Ticket already had been assigned to another attendee, please revoke it before to assign it again."
                    );
            }

            // try to get member and attendee by email
            $member = $this->member_repository->getByEmail($email);
            $attendee = $summit->getAttendeeByEmail($email);

            if (is_null($attendee) && !is_null($member)) {
                // if we have a member, try to get attendee by member
                Log::debug
                (
                    sprintf
                    (
                        "SummitOrderService::_assignTicket - attendee does not exists for email %s trying to get by member",
                        $email
                    )
                );
                $attendee = $summit->getAttendeeByMember($member);
            }

            if (is_null($attendee)) {
                // if attendee did not exist , create a new one
                Log::debug
                (
                    sprintf
                    (
                        "SummitOrderService::_assignTicket - attendee does not exists for email %s creating it",
                        $email
                    )
                );
                $attendee = SummitAttendeeFactory::build($summit, [
                    'email' => $email,
                ], $member);
            }

            // normalize payload
            $first_name = $payload['attendee_first_name'] ?? null;
            $last_name = $payload['attendee_last_name'] ?? null;
            $company = $payload['attendee_company'] ?? null;
            $extra_questions = $payload['extra_questions'] ?? [];
            $disclaimer_accepted = $payload['disclaimer_accepted'] ?? false;

            $normalize_payload = [
                'email' => $email,
                'extra_questions' => $extra_questions,
                'disclaimer_accepted' => $disclaimer_accepted,
            ];

            if (!is_null($first_name))
                $normalize_payload['first_name'] = trim($first_name);

            if (!is_null($last_name))
                $normalize_payload['last_name'] = trim($last_name);

            if (!is_null($company))
                $normalize_payload['company'] = trim($company);

            // update attendee data with custom payload
            $attendee = SummitAttendeeFactory::populate
            (
                $summit,
                $attendee,
                $normalize_payload,
                $member
            );

            $attendee->addTicket($ticket);

            $ticket->generateQRCode();
            $ticket->generateHash();
            $attendee->updateStatus();
            if ($summit->isRegistrationSendTicketEmailAutomatically())
                $attendee->sendInvitationEmail($ticket, false, $payload);

            return $ticket;

        });

    }

    /**
     * @param int $order_id
     * @param int $ticket_id
     * @param array $payload
     * @return SummitAttendeeTicket
     * @throws \Exception
     */
    public function reInviteAttendee(int $order_id, int $ticket_id, array $payload): SummitAttendeeTicket
    {

        return $this->tx_service->transaction(function () use ($order_id, $ticket_id, $payload) {

            Log::debug
            (
                sprintf
                (
                    "SummitOrderService::reInviteAttendee order id %s ticket id %s payload %s",
                    $order_id,
                    $ticket_id,
                    json_encode($payload)
                )
            );

            $order = $this->order_repository->getByIdExclusiveLock($order_id);

            if (!$order instanceof SummitOrder)
                throw new EntityNotFoundException("order not found");

            $ticket = $order->getTicketById($ticket_id);

            if (is_null($ticket))
                throw new EntityNotFoundException("ticket not found");

            if (!$ticket->isPaid())
                throw new ValidationException("ticket is not paid");

            $attendee = $ticket->getOwner();

            if (is_null($attendee))
                throw new EntityNotFoundException("attendee not found");

            $ticket->generateQRCode();
            $ticket->generateHash();

            $attendee->sendInvitationEmail($ticket, false, $payload);

            return $ticket;
        });
    }

    /**
     * @param int $order_id
     * @return SummitOrder
     * @throws \Exception
     */
    public function reSendOrderEmail(int $order_id): SummitOrder
    {

        return $this->tx_service->transaction(function () use ($order_id) {

            $order = $this->order_repository->getByIdExclusiveLock($order_id);

            if (is_null($order) || !$order instanceof SummitOrder)
                throw new EntityNotFoundException("order not found");

            Log::debug(sprintf("SummitOrderService:: reSendOrderEmail order %s", $order_id));

            if (!$order->hasOwner()) {
                // owner is not registered ...
                Log::debug("SummitOrderService::reSendOrderEmail - order has not owner set");
                $ownerEmail = $order->getOwnerEmail();
                // check if we have a member on db
                Log::debug(sprintf("SummitOrderService::reSendOrderEmail - trying to get email %s from db", $ownerEmail));
                $member = $this->member_repository->getByEmail($ownerEmail);

                if (!is_null($member)) {
                    // its turns out that email was registered as a member
                    // set the owner and move on
                    Log::debug(sprintf("SummitOrderService::reSendOrderEmail - member %s found at db", $ownerEmail));
                    $order->setOwner($member);

                    Log::debug("SummitOrderService::reSendOrderEmail - sending email to owner");
                    // send email to owner;
                    $this->sendExistentSummitOrderOwnerEmail($order);

                    return $order;
                }

                Log::debug(sprintf("SummitOrderService::reSendOrderEmail trying to get external user %s", $ownerEmail));

                $user = $this->member_service->checkExternalUser($ownerEmail);

                if (is_null($user)) {

                    Log::debug
                    (
                        sprintf
                        (
                            "SummitOrderService::reSendOrderEmail - user %s does not exist at IDP, emiting a registration request on idp",
                            $ownerEmail
                        )
                    );

                    // user does not exists , emit a registration request
                    // need to send email with set password link

                    $this->sendSummitOrderOwnerInvitationEmail($order, $this->member_service->emitRegistrationRequest
                    (
                        $ownerEmail,
                        $order->getOwnerFirstName(),
                        $order->getOwnerSurname(),
                        $order->getOwnerCompanyName()
                    ));

                    return $order;
                }

                Log::debug
                (
                    sprintf
                    (
                        "SummitOrderService::reSendOrderEmail - Creating a local user for %s",
                        $ownerEmail
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
                    $order = $this->order_repository->getByIdExclusiveLock($order_id);
                }

                // add the order to newly created member
                $member->addSummitRegistrationOrder($order);
            }

            // send email to owner
            $this->sendExistentSummitOrderOwnerEmail($order);
            return $order;
        });
    }

    /**
     * @param int $order_id
     * @param int $ticket_id
     * @param Member $currentUser
     * @param string|null $notes
     * @return SummitAttendeeTicket
     * @throws \Exception
     */
    public function cancelRequestRefundTicket(int $order_id, int $ticket_id, Member $currentUser, ?string $notes = null): SummitAttendeeTicket
    {
        return $this->tx_service->transaction(function () use ($order_id, $ticket_id, $currentUser, $notes) {

            $order = $this->order_repository->getById($order_id);
            if (is_null($order) || !$order instanceof SummitOrder)
                throw new EntityNotFoundException('Order not found.');

            $ticket = $order->getTicketById($ticket_id);

            if (is_null($ticket) || !$ticket instanceof SummitAttendeeTicket)
                throw new EntityNotFoundException('Ticket not found.');

            $ticket->cancelRefundRequest($currentUser, $notes);

            return $ticket;
        });
    }

    /**
     * @param Member $current_user ,
     * @param int $order_id
     * @param int $ticket_id
     * @return SummitAttendeeTicket
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function requestRefundTicket(Member $current_user, int $order_id, int $ticket_id): SummitAttendeeTicket
    {
        return $this->tx_service->transaction(function () use ($current_user, $order_id, $ticket_id) {

            // only owner of the order could request a refund on a ticket
            $order = $current_user->getSummitRegistrationOrderById($order_id);
            if (is_null($order))
                throw new EntityNotFoundException('Order not found.');

            if (!$order->getSummit()->canEmitRefundRequests($current_user)) {
                throw new ValidationException
                (
                    sprintf
                    (
                        "Refund Period is over for Summit %s.",
                        $order->getSummit()->getName()
                    )
                );
            }
            $ticket
                = $order->getTicketById($ticket_id);
            if (is_null($ticket))
                throw new EntityNotFoundException('Ticket not found.');

            if ($ticket->isFree()) {
                throw new ValidationException("You can not request a refund because ticket is free.");
            }

            $ticket->requestRefund($current_user);

            return $ticket;
        });
    }

    /**
     * @param Member $current_user
     * @param int $order_id
     * @return SummitOrder
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function requestRefundOrder(Member $current_user, int $order_id): SummitOrder
    {
        return $this->tx_service->transaction(function () use ($current_user, $order_id) {
            // only owner of the order could request a refund on a ticket
            $order = $current_user->getSummitRegistrationOrderById($order_id);
            if (is_null($order))
                throw new EntityNotFoundException('Order not found.');

            if (!$order->getSummit()->canEmitRefundRequests($current_user)) {
                throw new ValidationException
                (
                    sprintf
                    (
                        "Refund Period is over for Summit %s.",
                        $order->getSummit()->getName()
                    )
                );
            }

            foreach ($order->getTickets() as $ticket) {
                if (!$ticket->isPaid()) continue;
                if (!$ticket->isActive()) continue;
                $this->requestRefundTicket($current_user, $order_id, $ticket->getId());
            }

            return $order;
        });
    }

    /**
     * @param Summit $summit
     * @param Member $currentUser
     * @param int|string $ticket_id
     * @param float $amount
     * @param string|null $notes
     * @return SummitAttendeeTicket
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function refundTicket(Summit $summit, Member $currentUser, $ticket_id, float $amount, ?string $notes): SummitAttendeeTicket
    {
        return $this->tx_service->transaction(function () use ($summit, $currentUser, $ticket_id, $amount, $notes) {

            $ticket = $this->ticket_repository->getByIdExclusiveLock(intval($ticket_id));

            if (is_null($ticket))
                $this->ticket_repository->getByNumberExclusiveLock(strval($ticket_id));

            if (is_null($ticket) || !$ticket instanceof SummitAttendeeTicket)
                throw new EntityNotFoundException('ticket not found');

            if ($amount <= 0.0) {
                throw new ValidationException("can not refund an amount lower than zero!");
            }

            if (!$ticket->canRefund($amount)) {
                throw new ValidationException
                (
                    sprintf
                    (
                        "Can not request a refund on Ticket %s.",
                        $ticket->getNumber()
                    )
                );
            }

            $order = $ticket->getOrder();

            if ($order->getSummitId() != $summit->getId())
                throw new EntityNotFoundException('ticket not found');

            $paymentGatewayRes = null;

            if ($order->hasPaymentInfo()) {

                try {
                    $payment_gateway = $summit->getPaymentGateWayPerApp
                    (
                        IPaymentConstants::ApplicationTypeRegistration,
                        $this->default_payment_gateway_strategy
                    );

                    if (is_null($payment_gateway)) {
                        throw new ValidationException(sprintf("Payment configuration is not set for summit %s", $summit->getId()));
                    }

                    Log::debug
                    (
                        sprintf
                        (
                            "SummitOrderService::refundTicket trying to refund on payment gateway cart id %s",
                            $order->getPaymentGatewayCartId()
                        )
                    );
                    $paymentGatewayRes = $payment_gateway->refundPayment
                    (
                        $order->getPaymentGatewayCartId(),
                        $amount,
                        $ticket->getCurrency()
                    );

                    Log::debug
                    (
                        sprintf
                        (
                            "SummitOrderService::refundTicket refunded payment gateway cart id %s payment gateway response %s",
                            $order->getPaymentGatewayCartId(),
                            $paymentGatewayRes
                        )
                    );
                } catch (\Exception $ex) {
                    Log::warning($ex);
                    throw new ValidationException($ex->getMessage());
                }
            }

            $ticket->refund($currentUser, $amount, $paymentGatewayRes, $notes);

            return $ticket;
        });
    }

    /**
     * @param Summit $summit
     * @param string $order_hash
     * @return SummitOrder
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function cancel(Summit $summit, string $order_hash): SummitOrder
    {
        return $this->tx_service->transaction(function () use ($summit, $order_hash) {

            Log::debug(sprintf("SummitOrderService::cancel summit %s order %s",$summit->getId(), $order_hash));
            $order = $this->order_repository->getByHashLockExclusive($order_hash);

            if (is_null($order) || !$order instanceof SummitOrder || $summit->getId() != $order->getSummitId())
                throw new EntityNotFoundException("order not found");

            list($tickets_to_return, $promo_codes_to_return) = $order->calculateTicketsAndPromoCodesToReturn();

            $this->restoreTicketsPromoCodes($summit, $tickets_to_return, $promo_codes_to_return);

            $order->setCancelled();

            return $order;
        });
    }

    /**
     * @param array $payload
     * @param Summit|null $summit
     * @throws \Exception
     */
    public function processPayment(array $payload, ?Summit $summit = null): void
    {
        $this->tx_service->transaction(function () use ($summit, $payload) {

            Log::debug(sprintf("SummitOrderService::processPayment cart_id %s", $payload['cart_id']));

            $order = $this->order_repository->getByPaymentGatewayCartIdExclusiveLock($payload['cart_id']);

            if (is_null($order) || !$order instanceof SummitOrder || (!is_null($summit) && $order->getSummitId() != $summit->getId())) {
                throw new EntityNotFoundException
                (
                    sprintf("There is no order with cart_id %s.", $payload['cart_id'])
                );
            }

            $summit = $order->getSummit();

            $payment_gateway = $summit->getPaymentGateWayPerApp
            (
                IPaymentConstants::ApplicationTypeRegistration,
                $this->default_payment_gateway_strategy
            );

            if (is_null($payment_gateway)) {
                throw new ValidationException(sprintf("Payment configuration is not set for summit %s.", $summit->getId()));
            }

            if ($payment_gateway->isSuccessFullPayment($payload)) {
                Log::debug("SummitOrderService::processPayment: payment is successful");
                $order->setPaid($payload);
                return;
            }

            $order->setPaymentError($payment_gateway->getPaymentError($payload));
        });
    }

    /**
     * @param int $minutes
     * @param int $max
     * @throws \Exception
     */
    public function confirmOrdersOlderThanNMinutes(int $minutes, int $max = 100): void
    {
        // done in this way to avoid db lock contention
        $orders = $this->tx_service->transaction(function () use ($minutes, $max) {
            return $this->order_repository->getAllConfirmedOlderThanXMinutes($minutes, $max);
        });

        foreach ($orders as $order) {
            $this->tx_service->transaction(function () use ($order) {

                try {
                    if (!$order instanceof SummitOrder) return;

                    $order = $this->order_repository->getByIdExclusiveLock($order->getId());
                    if (!$order instanceof SummitOrder) return;
                    Log::debug(sprintf("SummitOrderService::confirmOrdersOlderThanNMinutes processing order %s", $order->getId()));
                    $summit = $order->getSummit();
                    $payment_gateway = $summit->getPaymentGateWayPerApp
                    (
                        IPaymentConstants::ApplicationTypeRegistration,
                        $this->default_payment_gateway_strategy
                    );
                    if (is_null($payment_gateway)) {
                        Log::warning(sprintf("SummitOrderService::confirmOrdersOlderThanNMinutes Payment configuration is not set for summit %s", $summit->getId()));
                        return;
                    }

                    $cart_id = $order->getPaymentGatewayCartId();
                    if (!empty($cart_id)) {
                        $status = $payment_gateway->getCartStatus($cart_id);
                        if (!is_null($status) && $payment_gateway->isSucceeded($status)) {

                            Log::info
                            (
                                sprintf
                                (
                                    "SummitOrderService::confirmOrdersOlderThanNMinutes marking as paid order %s create at %s",
                                    $order->getNumber(),
                                    $order->getCreated()->format("Y-m-d h:i:sa")
                                )
                            );

                            $order->setPaid($payment_gateway->getCartCreditCardInfo($cart_id));
                            // invoke now to avoid delays
                            $this->processInvitation($order);
                        }

                    }
                } catch (\Exception $ex) {
                    Log::warning($ex);
                }
            });
        }
    }

    /**
     * @param int $minutes
     * @param int $max
     * @throws \Exception
     */
    public function revokeReservedOrdersOlderThanNMinutes(int $minutes, int $max = 100): void
    {
        Log::debug(sprintf("SummitOrderService::revokeReservedOrdersOlderThanNMinutes minutes %s max %s", $minutes, $max));

        // done in this way to avoid db lock contention
        $orders = $this->tx_service->transaction(function () use ($minutes, $max) {
            return $this->order_repository->getAllReservedOlderThanXMinutes($minutes, $max);
        });

        Log::debug(sprintf("SummitOrderService::revokeReservedOrdersOlderThanNMinutes got %s orders to revoke", count($orders)));

        foreach ($orders as $order) {
            $this->tx_service->transaction(function () use ($order) {

                try {
                    if (!$order instanceof SummitOrder) return;

                    $order = $this->order_repository->getByIdExclusiveLock($order->getId());
                    if (!$order instanceof SummitOrder) return;
                    $summit = $order->getSummit();
                    Log::debug(sprintf("SummitOrderService::revokeReservedOrdersOlderThanNMinutes processing order %s summit %s", $order->getId(), $summit->getId()));
                    $payment_gateway = $summit->getPaymentGateWayPerApp
                    (
                        IPaymentConstants::ApplicationTypeRegistration,
                        $this->default_payment_gateway_strategy
                    );

                    if (is_null($payment_gateway)) {
                        Log::warning(sprintf("SummitOrderService::revokeReservedOrdersOlderThanNMinutes Payment configuration is not set for summit %s", $summit->getId()));
                        return;
                    }

                    Log::warning(sprintf("SummitOrderService::revokeReservedOrdersOlderThanNMinutes cancelling order reservation %s create at %s", $order->getNumber(), $order->getCreated()->format("Y-m-d h:i:sa")));

                    $cart_id = $order->getPaymentGatewayCartId();
                    if (!empty($cart_id)) {

                        $status = $payment_gateway->getCartStatus($cart_id);
                        if (!is_null($status)) {
                            if (!$payment_gateway->canAbandon($status)) {
                                Log::warning(sprintf("SummitOrderService::revokeReservedOrdersOlderThanNMinutes reservation %s created at %s can not be cancelled external status %s", $order->getId(), $order->getCreated()->format("Y-m-d h:i:sa"), $status));
                                if ($payment_gateway->isSucceeded($status)) {
                                    $order->setPaid($payment_gateway->getCartCreditCardInfo($cart_id));
                                    // invoke now to avoid delays
                                    $this->processInvitation($order);
                                }
                                return;
                            }
                            $payment_gateway->abandonCart($cart_id);
                        }
                    }

                    list($tickets_to_return, $promo_codes_to_return) = $order->calculateTicketsAndPromoCodesToReturn();

                    $this->restoreTicketsPromoCodes($summit, $tickets_to_return, $promo_codes_to_return);

                    $order->setCancelled();

                    Log::warning(sprintf("SummitOrderService::revokeReservedOrdersOlderThanNMinutes order %s got cancelled", $order->getId()));
                } catch (\Exception $ex) {
                    Log::warning($ex);
                }
            });
        }
    }

    /**
     * @param $ticket_id
     * @param string $format
     * @param Member|null $current_user
     * @param int|null $order_id
     * @param Summit|null $summit
     * @return string
     */
    public function renderTicketByFormat($ticket_id, string $format = "pdf", ?Member $current_user = null, ?int $order_id = null, ?Summit $summit = null): string
    {
        return $this->tx_service->transaction(function () use ($ticket_id, $current_user, $format, $order_id, $summit) {

            //try first by id
            $ticket = null;
            if (is_integer($ticket_id)) {
                Log::debug(sprintf("SummitOrderService::renderTicketByFormattrying to get ticket by id %s", $ticket_id));
                $ticket = $this->ticket_repository->getByIdExclusiveLock(intval($ticket_id));
            }

            if (is_null($ticket) && is_null($current_user)) {
                // try to get by hash
                Log::debug(sprintf("SummitOrderService::renderTicketByFormat trying to get ticket by hash %s", $ticket_id));
                $ticket = $this->ticket_repository->getByHashExclusiveLock(strval($ticket_id));

                if (is_null($ticket)) {
                    $ticket = $this->ticket_repository->getByFormerHashExclusiveLock(strval($ticket_id));
                    if (is_null($ticket))
                        throw new ValidationException("ticket hash is not valid");
                }

                if (is_null($ticket) || !$ticket->hasOwner())
                    throw new EntityNotFoundException("ticket not found");

                if (!$ticket->canPubliclyEdit()) {
                    // check hash lifetime
                    throw new ValidationException("ticket hash is not valid");
                }
            }

            if (is_null($ticket) || !$ticket instanceof SummitAttendeeTicket)
                throw new EntityNotFoundException("ticket not found");

            Log::debug(sprintf("SummitOrderService::renderTicketByFormat ticket id %s ticket status %s", $ticket->getId(), $ticket->getStatus()));
            if (!is_null($summit) && $ticket->getOrder()->getSummitId() !== $summit->getId())
                throw new EntityNotFoundException("ticket not found");

            if (!is_null($order_id) && $ticket->getOrderId() !== $order_id)
                throw new EntityNotFoundException("ticket not found");


            if (!$ticket->isPaid())
                throw new ValidationException("ticket is not paid");

            if (!is_null($current_user)) {
                // if current user is present
                // check rendering permissions ( order owner or ticket owner only)
                $allow_2_render = false;
                $order = $ticket->getOrder();

                if ($order->hasOwner() && $order->getOwnerEmail() == $current_user->getEmail()) {
                    $allow_2_render = true;
                }

                if ($ticket->hasOwner() && $ticket->getOwnerEmail() == $current_user->getEmail()) {
                    $allow_2_render = true;
                }

                if (!$allow_2_render)
                    throw new ValidationException("ticket does not belong to member");

            }

            $renderer = new SummitAttendeeTicketPDFRenderer($ticket);
            return $renderer->render();
        });
    }

    /**
     * @param string $hash
     */
    public function regenerateTicketHash(string $hash): void
    {
        $this->tx_service->transaction(function () use ($hash) {

            $ticket = $this->ticket_repository->getByHashExclusiveLock($hash);

            if (is_null($ticket)) {
                $ticket = $this->ticket_repository->getByFormerHashExclusiveLock($hash);
            }

            if (is_null($ticket))
                throw new EntityNotFoundException("ticket not found");

            $ticket->sendPublicEditEmail();
        });
    }

    /**
     * @param string $hash
     * @return SummitAttendeeTicket
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function getTicketByHash(string $hash): SummitAttendeeTicket
    {
        return $this->tx_service->transaction(function () use ($hash) {
            $ticket = $this->ticket_repository->getByHashExclusiveLock($hash);

            if (is_null($ticket)) {
                $ticket = $this->ticket_repository->getByFormerHashExclusiveLock($hash);
                if (!is_null($ticket))
                    throw new ValidationException("ticket hash is not valid");
            }

            if (is_null($ticket))
                throw new EntityNotFoundException("ticket not found");

            if (!$ticket->isPaid())
                throw new ValidationException("ticket is not paid");

            if (!$ticket->hasOwner())
                throw new ValidationException("ticket must have an assigned owner");

            if (!$ticket->canPubliclyEdit())
                throw new ValidationException("ticket hash is not valid");

            return $ticket;
        });
    }

    /**
     * @param Summit $summit
     * @param array $payload
     * @return SummitOrder
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function createOfflineOrder(Summit $summit, array $payload): SummitOrder
    {
        $order = $this->tx_service->transaction(function () use ($summit, $payload) {

            Log::debug(sprintf("SummitOrderService::createOfflineOrder summit %s payload %s", $summit->getId(), json_encode($payload)));
            // lock ticket type stock
            $owner = null;
            $ticket_type = $this->ticket_type_repository->getByIdExclusiveLock(intval($payload['ticket_type_id']));

            if (is_null($ticket_type) || !$ticket_type instanceof SummitTicketType || $ticket_type->getSummitId() != $summit->getId()) {
                Log::warning("SummitOrderService::createOfflineOrder ticket type not found");
                throw new EntityNotFoundException("ticket type not found");
            }

            // check owner
            if (isset($payload['owner_id'])) {
                Log::debug(sprintf("SummitOrderService::createOfflineOrder trying to get member by id %s", $payload['owner_id']));
                $owner = $this->member_repository->getById(intval($payload['owner_id']));
                if (is_null($owner)) {
                    Log::warning("SummitOrderService::createOfflineOrder owner not found");
                    throw new EntityNotFoundException("owner not found");
                }
            }

            if (is_null($owner) && isset($payload['owner_email'])) {
                Log::debug(sprintf("SummitOrderService::createOfflineOrder trying to get member by email %s", $payload['owner_email']));
                // if not try by email
                $owner = $this->member_repository->getByEmail(trim($payload['owner_email']));
            }

            // try to get attendee
            $attendee = !is_null($owner) ? $summit->getAttendeeByMember($owner) : null;

            if (is_null($attendee) && isset($payload['owner_email'])) {
                Log::debug(sprintf("SummitOrderService::createOfflineOrder trying to get attendee by email %s", $payload['owner_email']));
                $attendee = $this->attendee_repository->getBySummitAndEmail($summit, trim($payload['owner_email']));
                if (!is_null($attendee))
                    Log::debug(sprintf("SummitOrderService::createOfflineOrder found attendee %s (%s).", $attendee->getId(), $attendee->getEmail()));
            }

            if (is_null($attendee) && isset($payload['attendee'])) {
                $attendee = $payload['attendee'];
            }

            if (is_null($attendee)) {
                // create it
                Log::debug(sprintf("SummitOrderService::createOfflineOrder attendee is null"));
                //first name
                $first_name = isset($payload['owner_first_name']) ? trim($payload['owner_first_name']) : null;
                if (empty($first_name) && !is_null($owner) && !is_null($owner->getFirstName())) $first_name = $owner->getFirstName();
                if (empty($first_name)) {
                    Log::warning("SummitOrderService::createOfflineOrder owner firstname is null");
                    throw new ValidationException("you must provide an owner_first_name or a valid owner_id");
                }
                // surname
                $surname = isset($payload['owner_last_name']) ? trim($payload['owner_last_name']) : null;
                if (empty($surname) && !is_null($owner) && !is_null($owner->getLastName())) $surname = $owner->getLastName();
                if (empty($surname)) {
                    Log::warning("SummitOrderService::createOfflineOrder owner surname is null");
                    throw new ValidationException("you must provide an owner_last_name or a valid owner_id");
                }
                // mail
                $email = isset($payload['owner_email']) ? trim($payload['owner_email']) : null;

                $company = isset($payload['owner_company']) ? trim($payload['owner_company']) : null;

                if (empty($email) && !is_null($owner)) $email = $owner->getEmail();
                if (empty($email)) {
                    Log::warning("SummitOrderService::createOfflineOrder owner email is null");
                    throw new ValidationException("you must provide an owner_email or a valid owner_id");
                }

                $attendee = SummitAttendeeFactory::build($summit, [
                    'first_name' => $first_name,
                    'last_name' => $surname,
                    'email' => $email,
                    'company' => $company
                ], $owner);

                $this->attendee_repository->add($attendee, true);
            }

            // create order

            $order = SummitOrderFactory::build($summit, $payload);

            $order->generateNumber();
            do {
                if (!$summit->existOrderNumber($order->getNumber()))
                    break;
                $order->generateNumber();
            } while (1);

            Log::debug(sprintf("SummitOrderService::createOfflineOrder order number %s", $order->getNumber()));

            $order->setPaymentMethodOffline();

            // create tickets
            $ticket_qty = isset($payload["ticket_qty"]) ? intval($payload["ticket_qty"]) : 1;

            Log::debug(sprintf("SummitOrderService::createOfflineOrder ticket_qty %s", $ticket_qty));

            $order = $this->createTicketsForOrder($order, $ticket_type, $ticket_qty, $payload['promo_code'] ?? null, $attendee);

            if (!is_null($owner)) {
                $owner->addSummitRegistrationOrder($order);
            }

            $summit->addAttendee($attendee);
            $summit->addOrder($order);
            $order->generateHash();
            $order->generateQRCode();

            return $order;
        });

        return $this->tx_service->transaction(function () use ($order) {
            $order->setPaid();
            Log::debug(sprintf("SummitOrderService::createOfflineOrder order number %s mark as paid", $order->getNumber()));
            return $order;
        });
    }

    /**
     * @param SummitOrder $order
     * @param SummitTicketType $ticket_type
     * @param int $ticket_qty
     * @param string|null $promo_code
     * @param SummitAttendee|null $attendee
     * @return SummitOrder
     * @throws \Exception
     */
    private function createTicketsForOrder
    (
        SummitOrder      $order,
        SummitTicketType $ticket_type,
        int              $ticket_qty = 1,
        ?string          $promo_code = null,
        ?SummitAttendee  $attendee = null
    ): SummitOrder
    {

        return $this->tx_service->transaction(function () use ($order, $ticket_type, $ticket_qty, $promo_code, $attendee) {

            $summit = $order->getSummit();

            $default_badge_type = $summit->getDefaultBadgeType();

            if (is_null($default_badge_type)) {
                Log::warning("SummitOrderService::createTicketsForOrder default_badge_type is null");
                throw new ValidationException(sprintf("summit %s does not has a default badge type", $summit->getId()));
            }

            for ($i = 0; $i < $ticket_qty; $i++) {

                $ticket = new SummitAttendeeTicket();
                $ticket->setOrder($order);

                if ($ticket_qty == 1 && !is_null($attendee))
                    $ticket->setOwner($attendee);

                if ($order->isPaid())
                    $ticket->setPaid();

                $ticket->setTicketType($ticket_type);
                $ticket->generateNumber();
                $ticket_type->sell(1);

                do {
                    if (!$this->ticket_repository->existNumber($ticket->getNumber()))
                        break;
                    $ticket->generateNumber();
                } while (1);

                Log::debug(sprintf("SummitOrderService::createTicketsForOrder ticket number %s", $ticket->getNumber()));

                if (!$ticket->hasBadge()) {
                    $ticket->setBadge(SummitBadgeType::buildBadgeFromType($default_badge_type));
                }

                // promo code usage
                if (!empty($promo_code)) {
                    $pc = $this->promo_code_repository->getByValueExclusiveLock($summit, trim($promo_code));
                    if (is_null($pc)) {
                        throw new EntityNotFoundException(sprintf("Promo code %s not found.", $promo_code));
                    }
                    Log::debug(sprintf("SummitOrderService::createTicketsForOrder applying promo code %s", $pc->getCode()));
                    $owner_email = !is_null($attendee) ? $attendee->getEmail() : $order->getOwnerEmail();
                    $pc->addUsage($owner_email);
                    $pc->applyTo($ticket);
                }

                $ticket->applyTaxes($summit->getTaxTypes()->toArray());
                $order->addTicket($ticket);
                $ticket->generateHash();
                $ticket->generateQRCode();
            }

            return $order;
        });
    }

    /**
     * @param Summit $summit
     * @param int $order_id
     * @param array $payload
     * @return SummitOrder
     */
    public function updateOrder(Summit $summit, int $order_id, array $payload): SummitOrder
    {
        return $this->tx_service->transaction(function () use ($summit, $order_id, $payload) {
            $order = $this->order_repository->getByIdExclusiveLock($order_id);
            if (is_null($order) || !$order instanceof SummitOrder)
                throw new EntityNotFoundException("Order not found.");

            // check owner
            $owner_email = $payload['owner_email'] ?? '';
            if (!empty($owner_email)) {
                Log::debug
                (
                    sprintf
                    (
                        "SummitOrderService::updateOrder new owner provided %s",
                        $owner_email
                    )
                );

                $payload['owner'] = $this->member_repository->getByEmail($owner_email);
            }

            SummitOrderFactory::populate($summit, $order, $payload);

            return $order;
        });
    }

    /**
     * @param Summit $summit
     * @param $tickets_to_return
     * @param $promo_codes_to_return
     * @return void
     */
    private function restoreTicketsPromoCodes(Summit $summit, $tickets_to_return, $promo_codes_to_return): void
    {

        // restore tickets and promo-codes

        Log::debug
        (
            sprintf
            (
                "SummitOrderService::restoreTicketsPromoCodes restoring tickets and promo codes for summit %s tickets_to_return %s promo_codes_to_return %s",
                $summit->getId(),
                json_encode($tickets_to_return),
                json_encode($promo_codes_to_return)
            )
        );

        foreach ($tickets_to_return as $ticket_type_id => $qty) {
            $ticket_type = $this->ticket_type_repository->getByIdExclusiveLock($ticket_type_id);
            if (!$ticket_type instanceof SummitTicketType) continue;
            Log::debug(sprintf("SummitOrderService::restoreTicketsPromoCodes compensating ticket type %s on %s usages", $ticket_type_id, $qty));
            try {
                $ticket_type->restore($qty);
            } catch (ValidationException $ex) {
                Log::warning($ex);
            }
        }

        // compensate promo codes usages

        foreach ($promo_codes_to_return as $code => $value) {
            $promo_code = $this->promo_code_repository->getByValueExclusiveLock($summit, $code);
            if (!$promo_code instanceof SummitRegistrationPromoCode) continue;
            $qty = $value["qty"];
            Log::debug(sprintf("SummitOrderService::restoreTicketsPromoCodes compensating promo code %s on %s usages", $code, $qty));
            try {
                $promo_code->removeUsage($qty, $value["owner_email"]);
            } catch (ValidationException $ex) {
                Log::warning($ex);
            }
        }

    }

    /**
     * @param Summit $summit
     * @param int $order_id
     * @return void
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function deleteOrder(Summit $summit, int $order_id)
    {

        $this->tx_service->transaction(function () use ($summit, $order_id) {

            Log::debug(sprintf("SummitOrderService::deleteOrder summit %s order id %s", $summit->getId(), $order_id));

            $order = $this->order_repository->getById($order_id);

            if (is_null($order) || !$order instanceof SummitOrder)
                throw new EntityNotFoundException("Order not found.");

            list($tickets_to_return, $promo_codes_to_return) = $order->calculateTicketsAndPromoCodesToReturn();

            foreach ($order->getTickets() as $ticket) {
                $ticket->setCancelled();
            }

            $this->restoreTicketsPromoCodes($summit, $tickets_to_return, $promo_codes_to_return);

            $summit->removeOrder($order);

        });

    }

    /**
     * @param Summit $summit
     * @param $ticket_id
     * @return SummitAttendeeTicket|null
     * @throws \Exception
     */
    public function getTicket(Summit $summit, $ticket_id): ?SummitAttendeeTicket
    {
        return $this->tx_service->transaction(function () use ($summit, $ticket_id) {

            Log::debug(sprintf("SummitOrderService::getTicket summit %s ticket id %s", $summit->getId(), $ticket_id));

            $strategy = $this->ticket_finder_strategy_factory->build($summit, $ticket_id);
            if(is_null($strategy))
                throw new EntityNotFoundException("Ticket not found.");

            $ticket = $strategy->find();

            if (!$ticket instanceof SummitAttendeeTicket)
                throw new EntityNotFoundException("Ticket not found.");

            if ($ticket->getOrder()->getSummitId() != $summit->getId()) {
                throw new ValidationException("Ticket does not belong to summit.");
            }

            return $ticket;
        });
    }

    /**
     * @param Summit $summit
     * @param int|string $ticket_id
     * @param int $type_id
     * @return SummitAttendeeBadge
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function updateBadgeType(Summit $summit, $ticket_id, int $type_id): SummitAttendeeBadge
    {
        return $this->tx_service->transaction(function () use ($summit, $ticket_id, $type_id) {
            $badge_type = $summit->getBadgeTypeById($type_id);
            if (is_null($badge_type))
                throw new EntityNotFoundException("Badge type not found.");

            $ticket = $this->ticket_repository->getByIdExclusiveLock(intval($ticket_id));
            if (is_null($ticket))
                $this->ticket_repository->getByNumberExclusiveLock(strval($ticket_id));

            if (is_null($ticket) || !$ticket instanceof SummitAttendeeTicket)
                throw new EntityNotFoundException('Ticket not found.');

            $order = $ticket->getOrder();

            if ($order->getSummitId() != $summit->getId())
                throw new EntityNotFoundException('Ticket not found.');

            if (!$ticket->hasBadge())
                throw new EntityNotFoundException('Badge not found.');

            $badge = $ticket->getBadge();

            $badge->setType($badge_type);

            return $badge;
        });
    }

    /**
     * @param Summit $summit
     * @param int $ticket_id
     * @param int $feature_id
     * @return SummitAttendeeBadge
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function addAttendeeBadgeFeature(Summit $summit, $ticket_id, int $feature_id): SummitAttendeeBadge
    {
        return $this->tx_service->transaction(function () use ($summit, $ticket_id, $feature_id) {

            $feature_type = $summit->getFeatureTypeById($feature_id);
            if (is_null($feature_type))
                throw new EntityNotFoundException("Feature type not found.");

            $ticket = $this->ticket_repository->getByIdExclusiveLock(intval($ticket_id));
            if (is_null($ticket))
                $this->ticket_repository->getByNumberExclusiveLock(strval($ticket_id));

            if (is_null($ticket) || !$ticket instanceof SummitAttendeeTicket)
                throw new EntityNotFoundException('Ticket not found.');

            $order = $ticket->getOrder();

            if ($order->getSummitId() != $summit->getId())
                throw new EntityNotFoundException('Ticket not found.');

            if (!$ticket->hasBadge())
                throw new EntityNotFoundException('Badge not found.');

            $badge = $ticket->getBadge();
            if ($badge->hasFeature($feature_type))
                throw new ValidationException(sprintf("Badge already has feature %s.", $feature_type->getName()));

            $badge->addFeature($feature_type);

            return $badge;
        });
    }

    /**
     * @param Summit $summit
     * @param int|string $ticket_id
     * @param int $feature_id
     * @return SummitAttendeeBadge
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function removeAttendeeBadgeFeature(Summit $summit, $ticket_id, int $feature_id): SummitAttendeeBadge
    {
        return $this->tx_service->transaction(function () use ($summit, $ticket_id, $feature_id) {
            $feature_type = $summit->getFeatureTypeById($feature_id);
            if (is_null($feature_type))
                throw new EntityNotFoundException("Feature type not found.");

            $ticket = $this->ticket_repository->getByIdExclusiveLock(intval($ticket_id));
            if (is_null($ticket))
                $this->ticket_repository->getByNumberExclusiveLock(strval($ticket_id));

            if (is_null($ticket) || !$ticket instanceof SummitAttendeeTicket)
                throw new EntityNotFoundException('Ticket not found.');

            $order = $ticket->getOrder();

            if ($order->getSummitId() != $summit->getId())
                throw new EntityNotFoundException('Ticket not found.');

            if (!$ticket->hasBadge())
                throw new EntityNotFoundException('Badge not found.');

            $badge = $ticket->getBadge();

            if (!$badge->hasFeature($feature_type)) {
                // check if its an inherited feature
                if ($badge->isInheritedFeature($feature_type)) {
                    $badgeType = $badge->getType();
                    throw new ValidationException
                    (
                        sprintf
                        (
                            "Feature %s can not be removed from Badge because it is inherited from its Badge Type %s (%s).",
                            $feature_type->getName(),
                            $badgeType->getName(),
                            $badgeType->getId()
                        )
                    );
                }
                throw new ValidationException(sprintf("Badge does not have feature %s.", $feature_type->getName()));
            }

            $badge->removeFeature($feature_type);

            return $badge;
        });
    }

    /**
     * @param Member $requestor
     * @param SummitAttendeeBadge $badge
     * @param SummitBadgeViewType $viewType
     * @return bool
     * @throws ValidationException
     */
    private function checkPrintingRights(Member $requestor, SummitAttendeeBadge $badge, SummitBadgeViewType $viewType): bool
    {
        // check rules

        $type = $badge->getType();
        $al = $type->getAccessLevelByName(SummitAccessLevelType::IN_PERSON);
        if (is_null($al)) {
            throw new ValidationException("You have a Virtual only ticket.");
        }

        if (!$type->allowsViewType($viewType)) {
            throw new ValidationException(sprintf("View Type %s is not allowed.", $viewType->getName()));
        }

        if (!$requestor->isAdmin()) {

            $inPersonCheckedIn = $badge->getTicket()->getOwner()->hasCheckedIn();
            if ($inPersonCheckedIn) {
                throw new ValidationException("You are already checked in.");
            }
        }
        return true;
    }

    /**
     * @param Summit $summit
     * @param $ticket_id
     * @param Member $requestor
     * @return SummitAttendeeBadge|null
     * @throws \Exception
     */
    private function getAttendeeBadge(Summit $summit, $ticket_id, Member $requestor): ?SummitAttendeeBadge
    {
        return $this->tx_service->transaction(function () use ($summit, $ticket_id, $requestor) {
            $ticket = null;
            // check by numeric id
            if (is_numeric($ticket_id))
                $ticket = $this->ticket_repository->getByIdExclusiveLock(intval($ticket_id));

            if (is_null($ticket) && is_string($ticket_id)) {
                // check by ticket number
                $ticket = $this->ticket_repository->getByNumberExclusiveLock(strval($ticket_id));
                // if not found ... check by external ticket id
                if (is_null($ticket))
                    $ticket = $this->ticket_repository->getByExternalAttendeeIdExclusiveLock($summit, strval($ticket_id));
            }

            if (is_null($ticket) || !$ticket instanceof SummitAttendeeTicket || !$ticket->isActive())
                throw new EntityNotFoundException('Ticket not found.');

            $order = $ticket->getOrder();
            $summit = $order->getSummit();

            if ($order->getSummitId() != $summit->getId())
                throw new EntityNotFoundException('Ticket not found.');

            if (!$ticket->hasBadge())
                throw new EntityNotFoundException('Badge not found.');

            $badge = $this->badge_repository->getByIdExclusiveLock($ticket->getBadgeId());

            if (is_null($badge) && !$badge instanceof SummitAttendeeBadge)
                throw new EntityNotFoundException('Badge not found.');

            return $badge;
        });
    }

    /**
     * @param Summit $summit
     * @param int|string $ticket_id
     * @param string $viewTypeName
     * @param Member $requestor
     * @param array $payload
     * @return SummitAttendeeBadge
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function printAttendeeBadge(Summit $summit, $ticket_id, string $viewTypeName, Member $requestor, array $payload = []): SummitAttendeeBadge
    {
        return $this->tx_service->transaction(function () use ($summit, $ticket_id, $viewTypeName, $requestor, $payload) {

            Log::debug
            (
                sprintf
                (
                    "SummitOrderService::printAttendeeBadge summit %s ticket %s view %s payload %s",
                    $summit->getId(),
                    $ticket_id,
                    $viewTypeName,
                    json_encode($payload)
                )
            );

            $viewType = $summit->getBadgeViewTypeByName($viewTypeName);

            if (is_null($viewType)) {

                $viewType = $summit->getBadgeViewTypeById(intval($viewTypeName));
                if (is_null($viewType)) {
                    throw new EntityNotFoundException(sprintf("View Type %s not found.", $viewTypeName));
                }
            }

            $badge = $this->getAttendeeBadge($summit, $ticket_id, $requestor);

            $this->checkPrintingRights($requestor, $badge, $viewType);

            $badge->printIt($requestor, $viewType);

            // do checkin on print
            $attendee = $badge->getTicket()->getOwner();

            $must_check_in = $payload['check_in'] ?? true;
            if ($must_check_in && !$attendee->hasCheckedIn()) {
                $attendee->setSummitHallCheckedIn(true);
            }

            return $badge;
        });
    }

    /**
     * @param Summit $summit
     * @param int|string $ticket_id
     * @param string $viewType
     * @param Member $requestor
     * @return SummitAttendeeBadge
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function canPrintAttendeeBadge(Summit $summit, $ticket_id, string $viewType, Member $requestor): SummitAttendeeBadge
    {
        return $this->tx_service->transaction(function () use ($summit, $ticket_id, $viewType, $requestor) {

            Log::debug
            (
                sprintf
                (
                    "SummitOrderService::canPrintAttendeeBadge summit %s ticket_id %s viewType %s",
                    $summit->getId(),
                    $ticket_id,
                    $viewType
                )
            );

            $view = $summit->getBadgeViewTypeByName($viewType);
            if (is_null($view)) {
                $view = $summit->getBadgeViewTypeById(intval($viewType));
                if (is_null($view)) {
                    throw new EntityNotFoundException(sprintf("View Type %s not found.", $viewType));
                }
            }
            $badge = $this->getAttendeeBadge($summit, $ticket_id, $requestor);
            $this->checkPrintingRights($requestor, $badge, $view);
            $badge->generateQRCode();
            return $badge;
        });
    }

    /**
     * @param Summit $summit
     * @param int|string $ticket_id
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function deleteBadge(Summit $summit, $ticket_id): void
    {
        $this->tx_service->transaction(function () use ($summit, $ticket_id) {
            $ticket = $this->ticket_repository->getByIdExclusiveLock(intval($ticket_id));
            if (is_null($ticket))
                $this->ticket_repository->getByNumberExclusiveLock(strval($ticket_id));

            if (is_null($ticket) || !$ticket instanceof SummitAttendeeTicket)
                throw new EntityNotFoundException('ticket not found');

            $order = $ticket->getOrder();
            $summit = $order->getSummit();

            if ($order->getSummitId() != $summit->getId())
                throw new EntityNotFoundException('ticket not found');

            if (!$ticket->hasBadge())
                throw new EntityNotFoundException('badge not found');

            $badge = $this->badge_repository->getByIdExclusiveLock($ticket->getBadgeId());

            $this->badge_repository->delete($badge);
        });
    }

    /**
     * @param Summit $summit
     * @param int|string $ticket_id
     * @param array $payload
     * @return SummitAttendeeBadge
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function createBadge(Summit $summit, $ticket_id, array $payload): SummitAttendeeBadge
    {
        return $this->tx_service->transaction(function () use ($summit, $ticket_id, $payload) {
            $ticket = $this->ticket_repository->getByIdExclusiveLock(intval($ticket_id));
            if (is_null($ticket))
                $this->ticket_repository->getByNumberExclusiveLock(strval($ticket_id));

            if (is_null($ticket) || !$ticket instanceof SummitAttendeeTicket)
                throw new EntityNotFoundException('ticket not found');

            $order = $ticket->getOrder();
            $summit = $order->getSummit();

            if ($order->getSummitId() != $summit->getId())
                throw new EntityNotFoundException('ticket not found');

            if ($ticket->hasBadge())
                throw new ValidationException('ticket already has a badge');

            $badge = new SummitAttendeeBadge();
            $badge_type = $summit->getDefaultBadgeType();
            if (isset($payload['badge_type_id'])) {
                $badge_type = $summit->getBadgeTypeById(intval($payload['badge_type_id']));

            }
            if (is_null($badge_type)) {
                throw new EntityNotFoundException("badge type not found");
            }
            $badge->setType($badge_type);
            if (isset($payload['features'])) {
                foreach ($payload['features'] as $feature_id) {
                    $feature = $summit->getFeatureTypeById($feature_id);
                    if (is_null($feature))
                        throw new EntityNotFoundException("feature type not found");
                    $badge->addFeature($feature);
                }
            }
            $ticket->setBadge($badge);
            return $badge;
        });
    }

    /**
     * @param Summit $summit
     * @param int $order_id
     * @param array $payload
     * @return SummitOrder
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function addTickets(Summit $summit, int $order_id, array $payload): SummitOrder
    {
        return $this->tx_service->transaction(function () use ($summit, $order_id, $payload) {
            $order = $this->order_repository->getByIdExclusiveLock($order_id);
            if (is_null($order) || !$order instanceof SummitOrder)
                throw new EntityNotFoundException("order not found");

            if ($summit->getId() != $order->getSummitId())
                throw new EntityNotFoundException("order not found");

            $ticket_type = $this->ticket_type_repository->getByIdExclusiveLock(intval($payload['ticket_type_id']));

            if (is_null($ticket_type) || !$ticket_type instanceof SummitTicketType || $ticket_type->getSummitId() != $summit->getId()) {
                Log::warning("SummitOrderService::addTicket ticket type not found");
                throw new EntityNotFoundException("ticket type not found");
            }

            $ticket_qty = isset($payload["ticket_qty"]) ? intval($payload["ticket_qty"]) : 1;

            $order = $this->createTicketsForOrder($order, $ticket_type, $ticket_qty, $payload['promo_code'] ?? null);

            return $order;
        });
    }

    /**
     * @param Member $current_user
     * @param int $ticket_id
     * @param array $payload
     * @return SummitAttendeeTicket
     * @throws \Exception
     */
    public function updateTicketById(Member $current_user, int $ticket_id, array $payload): SummitAttendeeTicket
    {
        return $this->tx_service->transaction(function () use ($current_user, $ticket_id, $payload) {

            Log::debug
            (
                sprintf
                (
                    "SummitOrderService::updateTicketById member %s ticket %s payload %s",
                    $current_user->getEmail(),
                    $ticket_id,
                    json_encode($payload)
                )
            );

            $ticket = $this->ticket_repository->getByIdExclusiveLock($ticket_id);

            if (is_null($ticket) || !$ticket instanceof SummitAttendeeTicket || !$ticket->isActive())
                throw new EntityNotFoundException("ticket not found");

            if (!$ticket->canEditTicket($current_user)) {
                throw new ValidationException(sprintf("Ticket %s can not be edited by current member", $ticket_id));
            }

            $order = $ticket->getOrder();
            $summit = $order->getSummit();
            $first_name = $payload['attendee_first_name'] ?? null;
            $last_name = $payload['attendee_last_name'] ?? null;
            $email = $payload['attendee_email'] ?? null;
            $company = $payload['attendee_company'] ?? null;
            $company_id = $payload['attendee_company_id'] ?? null;
            $extra_questions = $payload['extra_questions'] ?? [];
            $disclaimer_accepted = $payload['disclaimer_accepted'] ?? null;

            if ($summit->isRegistrationDisclaimerMandatory()) {
                $disclaimer_accepted = boolval($payload['disclaimer_accepted'] ?? false);
                if (!$disclaimer_accepted)
                    throw new ValidationException("Disclaimer is Mandatory.");
            }

            $attendee = $ticket->getOwner();

            if (!is_null($attendee)) {
                if ($attendee->getEmail() != $email)
                    throw new ValidationException
                    (
                        "Ticket already had been assigned to another attendee, please revoke it before to assign it again."
                    );
            }

            $payload = [
                'first_name' => $first_name,
                'last_name' => $last_name,
                'company' => $company,
                'company_id' => $company_id,
                'email' => $email,
                'extra_questions' => $extra_questions,
            ];

            if (!is_null($disclaimer_accepted)) {
                $payload['disclaimer_accepted'] = boolval($disclaimer_accepted);
            }

            if (is_null($attendee) && !empty($attendee_email)) {
                // try to create it
                $attendee = $this->attendee_repository->getBySummitAndEmail($summit, $attendee_email);
                if (is_null($attendee)) {
                    $attendee = new SummitAttendee();
                }
            }

            if (!is_null($attendee)) {
                // update it
                Log::debug
                (
                    sprintf
                    (
                        "SummitOrderService::updateTicketById member %s ticket %s updating attendee %s.",
                        $current_user->getEmail(),
                        $ticket_id,
                        $attendee->getId()
                    )
                );

                SummitAttendeeFactory::populate($summit, $attendee, $payload, !empty($email) ? $this->member_repository->getByEmail($email) : null);
                $attendee->addTicket($ticket);
                $attendee->updateStatus();
                if ($summit->isRegistrationSendTicketEmailAutomatically()) {
                    Log::debug
                    (
                        sprintf
                        (
                            "SummitOrderService::updateTicketById member %s ticket %s sending invitation email to attendee %s.",
                            $current_user->getEmail(),
                            $ticket_id,
                            $attendee->getId()
                        )
                    );
                    $attendee->sendInvitationEmail($ticket);
                }
            }

            return $ticket;
        });
    }

    /**
     * @param Summit $summit
     * @param int $order_id
     * @param int $ticket_id
     * @param array $payload
     * @return SummitAttendeeTicket
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function updateTicket(Summit $summit, int $order_id, int $ticket_id, array $payload): SummitAttendeeTicket
    {
        list($ticket, $shouldSendInvitationEmail) = $this->tx_service->transaction(function () use ($summit, $order_id, $ticket_id, $payload) {
            // lock and get the order
            $order = $this->order_repository->getByIdExclusiveLock($order_id);

            if (is_null($order) || !$order instanceof SummitOrder)
                throw new EntityNotFoundException("order not found");

            if ($order->getSummitId() != $summit->getId()) {
                throw new EntityNotFoundException("order not found");
            }

            $summit = $order->getSummit();
            $ticket = $order->getTicketById($ticket_id);

            if (is_null($ticket))
                throw new EntityNotFoundException("ticket not found");

            if (!$ticket->isPaid())
                throw new ValidationException("ticket is not paid");

            $ticket->generateQRCode();
            $ticket->generateHash();

            $owner = $ticket->getOwner();

            // check if we are doing a assign / re assign
            $attendee_email = $payload['attendee_email'] ?? null;
            $new_owner = null;

            if (!empty($attendee_email)) {

                // first try to get the new owner by email

                $new_owner = $this->attendee_repository->getBySummitAndEmail($summit, $attendee_email);

                if (is_null($new_owner)) {
                    Log::debug(sprintf("attendee %s does no exists .. creating it ", $attendee_email));
                    $attendee_payload = [
                        'email' => $attendee_email
                    ];

                    $new_owner = SummitAttendeeFactory::build
                    (
                        $summit,
                        $attendee_payload,
                        $this->member_repository->getByEmail($attendee_email)
                    );

                    $this->attendee_repository->add($new_owner);
                }

                // populate the new owner with extra data
                $attendee_payload = [];

                if (isset($payload['attendee_first_name']))
                    $attendee_payload['first_name'] = $payload['attendee_first_name'];

                if (isset($payload['attendee_last_name']))
                    $attendee_payload['last_name'] = $payload['attendee_last_name'];

                if (isset($payload['attendee_company']))
                    $attendee_payload['company'] = $payload['attendee_company'];

                if (isset($payload['attendee_company_id']))
                    $attendee_payload['company_id'] = intval($payload['attendee_company_id']);

                if (isset($payload['extra_questions']))
                    $attendee_payload['extra_questions'] = $payload['extra_questions'];

                SummitAttendeeFactory::populate($summit, $new_owner, $attendee_payload, $new_owner->getMember());
            }

            $shouldSendInvitationEmail = false;
            // we are doing a reassignment from owner to new owner
            if (!is_null($owner) && !is_null($new_owner) && $owner->getId() !== $new_owner->getId()) {
                $owner->sendRevocationTicketEmail($ticket);
                $owner->removeTicket($ticket);
                $owner->updateStatus();
            }

            // if we have a new owner set the ticket
            if (!is_null($new_owner)) {
                $new_owner->addTicket($ticket);
                $ticket->generateQRCode();
                $ticket->generateHash();
                $new_owner->updateStatus();
                $shouldSendInvitationEmail = true;
            }

            if (isset($payload['ticket_type_id'])) {
                // set ticket type
                $ticket_type_id = intval($payload['ticket_type_id']);
                $ticket_type = $summit->getTicketTypeById($ticket_type_id);
                if (is_null($ticket_type))
                    throw new EntityNotFoundException("ticket type not found");

                $ticket->upgradeTicketType($ticket_type);

                $shouldSendInvitationEmail = true;
            }

            if (isset($payload['badge_type_id'])) {
                // set badge type
                $badge_type_id = intval($payload['badge_type_id']);
                $badge_type = $summit->getBadgeTypeById($badge_type_id);
                if (is_null($badge_type))
                    throw new EntityNotFoundException("badge type not found");

                $badge = $ticket->hasBadge() ? $ticket->getBadge() : new SummitAttendeeBadge();
                $badge->setType($badge_type);
                $ticket->setBadge($badge);
            }

            return [$ticket, $shouldSendInvitationEmail];
        });

        if ($shouldSendInvitationEmail && $summit->isRegistrationSendTicketEmailAutomatically() && $ticket->hasOwner())
            $ticket->getOwner()->sendInvitationEmail($ticket);

        return $ticket;
    }

    /**
     * @param string $hash
     * @param array $payload
     * @return SummitAttendeeTicket
     * @throws \Exception
     */
    public function updateTicketByHash(string $hash, array $payload): SummitAttendeeTicket
    {
        return $this->tx_service->transaction(function () use ($hash, $payload) {

            $ticket = $this->ticket_repository->getByHashExclusiveLock($hash);

            if (is_null($ticket) || !$ticket->isActive())
                throw new EntityNotFoundException("Ticket not found.");

            if (!$ticket->isPaid())
                throw new ValidationException("Ticket is not paid.");

            if (!$ticket->hasOwner())
                throw new ValidationException("Ticket must have an assigned owner.");

            if (!$ticket->canPubliclyEdit())
                throw new ValidationException("Ticket hash is not valid.");

            $attendee = $ticket->getOwner();
            $summit = $ticket->getOrder()->getSummit();

            if ($summit->isRegistrationDisclaimerMandatory()) {
                $disclaimer_accepted = boolval($payload['disclaimer_accepted'] ?? false);
                if (!$disclaimer_accepted)
                    throw new ValidationException("disclaimer_accepted is mandatory.");
            }

            $first_name = $payload['attendee_first_name'] ?? '';
            $company = $payload['attendee_company'] ?? null;
            $company_id = $payload['attendee_company_id'] ?? null;
            $last_name = $payload['attendee_last_name'] ?? '';
            $extra_questions = $payload['extra_questions'] ?? [];

            $disclaimer_accepted = $payload['disclaimer_accepted'] ?? null;
            $reduced_payload = [
                'first_name' => $first_name,
                'last_name' => $last_name,
                'company' => $company,
                'company_id' => $company_id,
                'extra_questions' => $extra_questions
            ];

            if (!is_null($disclaimer_accepted)) {
                $reduced_payload['disclaimer_accepted'] = boolval($disclaimer_accepted);
            }

            // update it
            SummitAttendeeFactory::populate($summit, $attendee, $reduced_payload);
            $attendee->updateStatus();
            if ($summit->isRegistrationSendTicketEmailAutomatically())
                $attendee->sendInvitationEmail($ticket);

            Event::dispatch(new TicketUpdated($attendee));

            return $ticket;
        });
    }

    /**
     * @param string $order_hash
     * @param array $payload
     * @return SummitOrder
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function updateTicketsByOrderHash(string $order_hash, array $payload): SummitOrder
    {
        return $this->tx_service->transaction(function () use ($order_hash, $payload) {

            Log::debug(sprintf("SummitOrderService::updateTicketsByOrderHash order hash %s", $order_hash));

            $tickets = $payload['tickets'] ?? [];
            $order = $this->order_repository->getByHashLockExclusive($order_hash);
            if (is_null($order))
                throw new EntityNotFoundException("Order not found.");

            if (!$order->canPubliclyEdit()) {
                // check hash lifetime
                throw new ValidationException("Order hash is not valid.");
            }

            $attendees_cache = [];
            foreach ($tickets as $ticket_payload) {

                Log::debug
                (
                    sprintf
                    (
                        "SummitOrderService::updateTicketsByOrderHash order hash %s ticket payload %s",
                        $order_hash,
                        json_encode($ticket_payload)
                    )
                );

                $ticket_id = intval($ticket_payload['id']);
                $ticket = $order->getTicketById($ticket_id);

                if (is_null($ticket) || !$ticket instanceof SummitAttendeeTicket)
                    throw new EntityNotFoundException("ticket not found");

                $summit = $order->getSummit();

                $first_name = $ticket_payload['attendee_first_name'] ?? null;
                $last_name = $ticket_payload['attendee_last_name'] ?? null;
                $email = $ticket_payload['attendee_email'] ?? null;
                $company = $ticket_payload['attendee_company'] ?? null;
                $company_id = $ticket_payload['attendee_company_id'] ?? null;
                $extra_questions = $ticket_payload['extra_questions'] ?? [];
                $disclaimer_accepted = $ticket_payload['disclaimer_accepted'] ?? null;

                if ($summit->isRegistrationDisclaimerMandatory()) {
                    $disclaimer_accepted = boolval($ticket_payload['disclaimer_accepted'] ?? false);
                    if (!$disclaimer_accepted)
                        throw new ValidationException("Disclaimer is Mandatory.");
                }

                $attendee = $ticket->getOwner();

                if (!is_null($attendee)) {
                    if ($attendee->getEmail() != $email)
                        throw new ValidationException
                        (
                            "Ticket already had been assigned to another attendee, please revoke it before to assign it again."
                        );
                }

                $payload = [
                    'first_name' => $first_name,
                    'last_name' => $last_name,
                    'company' => $company,
                    'company_id' => $company_id,
                    'email' => $email,
                    'extra_questions' => $extra_questions
                ];

                if (!is_null($disclaimer_accepted)) {
                    $payload['disclaimer_accepted'] = boolval($disclaimer_accepted);
                }


                if (is_null($attendee) && !empty($email)) {
                    Log::debug(sprintf("SummitOrderService::updateTicketsByOrderHash attendee does not exists"));
                    // try to create it
                    $attendee = $this->attendee_repository->getBySummitAndEmail($summit, $email);
                    if (is_null($attendee)) {
                        // check if we have in memory already
                        $attendee = $attendees_cache[$email] ?? null;
                    }

                    if (is_null($attendee)) {
                        Log::debug(sprintf("SummitOrderService::updateTicketsByOrderHash creating new attendee for email %s", $email));
                        $attendee = new SummitAttendee();
                    }
                }

                if (!is_null($attendee)) {

                    // update it
                    SummitAttendeeFactory::populate($summit, $attendee, $payload, !empty($email) ? $this->member_repository->getByEmail($email) : null);
                    // we store it on memory just in case that we have the case of multiple tickets for the same attendee
                    $attendees_cache[$attendee->getEmail()] = $attendee;
                    $attendee->updateStatus();
                    if ($summit->isRegistrationSendTicketEmailAutomatically())
                        $attendee->sendInvitationEmail($ticket);
                    $attendee->addTicket($ticket);
                }
            }

            return $order;
        });
    }

    /**
     * @param Summit $summit
     * @param UploadedFile $csv_file
     * @throws ValidationException
     */
    public function importTicketData(Summit $summit, UploadedFile $csv_file): void
    {
        Log::debug(sprintf("SummitOrderService::importTicketData - summit %s", $summit->getId()));

        $allowed_extensions = ['txt'];

        if (!in_array($csv_file->extension(), $allowed_extensions)) {
            throw new ValidationException("file does not has a valid extension ('csv').");
        }

        $real_path = $csv_file->getRealPath();
        $filename = pathinfo($real_path);
        $filename = $filename['filename'] ?? sprintf("file%s", time());
        $basename = sprintf("%s_%s.csv", $filename, time());
        $path = "tmp/tickets_imports";
        $csv_data = File::get($real_path);
        if (empty($csv_data))
            throw new ValidationException("file content is empty!");

        $this->upload_strategy->save($csv_file, $path, $basename);

        $reader = CSVReader::buildFrom($csv_data);

        // check needed columns (headers names)
        /*
            columns
            * id
            * number
            * attendee_email ( mandatory if id and number are missing)
            * attendee_first_name (mandatory)
            * attendee_last_name (mandatory)
            * attendee_tags (optional)
            * attendee_company (optional)
            * attendee_company_id (optional)
            * ticket_type_name ( mandatory if id and number are missing)
            * ticket_type_id ( mandatory if id and number are missing)
            * promo_code_id (optional)
            * promo_code (optional)
            * ticket_promo_code (optional)
            * badge_type_id (optional)
            * badge_type_name (optional)
            * one col per feature
         */

        // validate format with col names
        $ticket_data_present = $reader->hasColumn("id") || $reader->hasColumn("number");
        $attendee_data_present = $reader->hasColumn("attendee_email") ||
            $reader->hasColumn("attendee_first_name") ||
            $reader->hasColumn("attendee_last_name");

        if (!$ticket_data_present && !$attendee_data_present)
            throw new ValidationException
            (
                "you must define a ticket id [id], ticket number [number] or 
                attendee data [attendee_email, attendee_first_name, attendee_last_name] on csv columns"
            );

        ProcessTicketDataImport::dispatch($summit->getId(), $basename);
    }

    /**
     * @param int $summit_id
     * @param string $filename
     * @throws EntityNotFoundException
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public function processTicketData(int $summit_id, string $filename)
    {
        $path = sprintf("tmp/tickets_imports/%s", $filename);
        Log::debug(sprintf("SummitOrderService::processTicketData summit %s filename %s", $summit_id, $filename));

        if (!$this->download_strategy->exists($path)) {
            Log::warning
            (
                sprintf
                (
                    "SummitOrderService::processTicketData file %s does not exist on storage %s",
                    $path,
                    $this->download_strategy->getDriver()
                )
            );

            throw new ValidationException(sprintf("file %s does not exists.", $filename));
        }

        $csv_data = $this->download_strategy->get($path);

        $summit = $this->tx_service->transaction(function () use ($summit_id) {
            $summit = $this->summit_repository->getById($summit_id);
            if (is_null($summit) || !$summit instanceof Summit)
                throw new EntityNotFoundException(sprintf("summit %s does not exists.", $summit_id));
            return $summit;
        });

        $reader = CSVReader::buildFrom($csv_data);

        $ticket_data_present = $reader->hasColumn("id") || $reader->hasColumn("number");
        $attendee_data_present = $reader->hasColumn("attendee_email") ||
            $reader->hasColumn("attendee_first_name") ||
            $reader->hasColumn("attendee_last_name");

        $badge_data_present = $reader->hasColumn("badge_type_id") || $reader->hasColumn("badge_type_name");

        foreach ($reader as $idx => $row) {

            $this->tx_service->transaction(function () use
            ($summit, $reader, $row, $ticket_data_present, $attendee_data_present, $badge_data_present) {

                Log::debug(sprintf("SummitOrderService::processTicketData processing row %s", json_encode($row)));

                $ticket = null;
                $attendee = null;
                // process ticket data (try to get an existent ticket)
                if ($ticket_data_present) {
                    Log::debug("SummitOrderService::processTicketData - has ticket data present ... trying to get ticket");

                    // edit already existent ticket ( could be assigned or not)
                    if ($reader->hasColumn("number")) {
                        Log::debug(sprintf("SummitOrderService::processTicketData trying to get ticket by number %s", $row['number']));
                        $ticket = $this->ticket_repository->getByNumberExclusiveLock($row['number']);
                    }

                    if (is_null($ticket) && $reader->hasColumn("id")) {
                        Log::debug(sprintf("SummitOrderService::processTicketData trying to get ticket by id %s", $row['id']));
                        $ticket = $this->ticket_repository->getByIdExclusiveLock(intval($row['id']));
                    }

                    if (!is_null($ticket) && !$ticket->isPaid()) {
                        Log::warning("SummitOrderService::processTicketData - ticket is not paid");
                        return;
                    }

                    if (!is_null($ticket) && !$ticket->isActive()) {
                        Log::warning("SummitOrderService::processTicketData - ticket is not active");
                        return;
                    }
                }
                // process attendee data  ( try to get an existent attendee or create a new one)
                if ($attendee_data_present) {
                    Log::debug(sprintf("SummitOrderService::processTicketData - has attendee data present ... trying to get attendee %s", $row['attendee_email']));
                    // check if attendee exists
                    $attendee = $this->attendee_repository->getBySummitAndEmail($summit, $row['attendee_email']);
                    $member = $this->member_repository->getByEmail($row['attendee_email']);

                    if (is_null($attendee)) {

                        Log::debug(sprintf("SummitOrderService::processTicketData - attendee %s does not exists", $row['attendee_email']));
                        // create attendee ( populate payload)
                        $payload = [
                            'email' => $row['attendee_email'],
                            'first_name' => $row['attendee_first_name'],
                            'last_name' => $row['attendee_last_name'],
                        ];

                        if ($reader->hasColumn('attendee_company')) {
                            $payload['company'] = $row['attendee_company'];
                        }

                        if ($reader->hasColumn('attendee_company_id')) {
                            $payload['company_id'] = intval($row['attendee_company_id']);
                        }

                        Log::debug(sprintf("SummitOrderService::processTicketData creating attendee with payload %s", json_encode($payload)));


                        $attendee = SummitAttendeeFactory::build($summit, $payload, $member);

                        if ($reader->hasColumn('attendee_tags')) {
                            $tags = explode('|', $row['attendee_tags']);
                            $attendee->clearTags();
                            foreach ($tags as $tag_val) {
                                $tag = $this->tags_repository->getByTag($tag_val);
                                if(is_null($tag)) continue;
                                $attendee->addTag($tag);
                            }
                        }
                        $this->attendee_repository->add($attendee);
                    }
                }

                if (!is_null($attendee)) {
                    if (is_null($ticket)) {
                        Log::debug(sprintf("SummitOrderService::processTicketData ticket is null, trying to create a new one"));

                        // create ticket
                        // first try to get ticket type
                        $ticket_type = null;
                        $promo_code = null;

                        if ($reader->hasColumn('ticket_type_name')) {
                            Log::debug(sprintf("SummitOrderService::processTicketData trying to get ticket type by name %s", $row['ticket_type_name']));
                            $ticket_type = $this->ticket_type_repository->getByType($summit, $row['ticket_type_name']);
                        }

                        if ($reader->hasColumn('ticket_promo_code')) {
                            Log::debug(sprintf("SummitOrderService::processTicketData trying to get promo code by code %s", $row['ticket_promo_code']));
                            $promo_code = $this->promo_code_repository->getByCode($row['ticket_promo_code']);
                        }

                        if ($reader->hasColumn('promo_code_id')) {
                            Log::debug(sprintf("SummitOrderService::processTicketData trying to get promo code by id %s", $row['promo_code_id']));
                            $promo_code = $this->promo_code_repository->getById(intval($row['promo_code_id']));
                        }

                        if (is_null($promo_code) && $reader->hasColumn('promo_code')) {
                            Log::debug(sprintf("SummitOrderService::processTicketData trying to get promo code by code %s", $row['promo_code']));
                            $promo_code = $this->promo_code_repository->getByCode($row['promo_code']);
                        }

                        if (is_null($ticket_type) && $reader->hasColumn('ticket_type_id')) {
                            Log::debug(sprintf("SummitOrderService::processTicketData trying to get ticket type by id %s", $row['ticket_type_id']));
                            $ticket_type = $this->ticket_type_repository->getById(intval($row['ticket_type_id']));
                        }

                        if (is_null($ticket_type)) {
                            Log::debug(sprintf("SummitOrderService::processTicketData - ticket type is not provide, ticket can not be created for attendee"));
                            return;
                        }

                        $order_payload =     [
                            'ticket_type_id' => $ticket_type->getId(),
                            'attendee' => $attendee,
                            'owner_email' => $attendee->getEmail(),
                            'owner_first_name' => $attendee->getFirstName(),
                            'owner_last_name' => $attendee->getSurname(),
                            'owner_company' => $attendee->getCompanyName(),
                        ];

                        if(!is_null($promo_code)){
                            Log::debug(sprintf("SummitOrderService::processTicketData adding promo code by code %s to offline order", $promo_code->getId()));
                            $order_payload['promo_code'] = $promo_code->getCode();
                        }

                        $order = $this->createOfflineOrder($summit, $order_payload);

                        $ticket = $order->getFirstTicket();

                    } else {
                        // ticket exists try to re assign it
                        Log::debug(sprintf("SummitOrderService::processTicketData ticket exists. trying to re assign it ..."));

                        if ($ticket->hasOwner() && $ticket->getOwnerEmail() != $attendee->getEmail()) {
                            Log::debug(sprintf("SummitOrderService::processTicketData - reasigning ticket to attendee %s", $attendee->getEmail()));
                            $ticket->getOwner()->sendRevocationTicketEmail($ticket);

                            $ticket->getOwner()->removeTicket($ticket);
                        }

                        Log::debug(sprintf("SummitOrderService::processTicketData assigning ticket %s to attendee %s", $ticket->getNumber(), $attendee->getEmail()));

                        $attendee->addTicket($ticket);

                        $ticket->generateQRCode();
                        $ticket->generateHash();

                        if ($summit->isRegistrationSendTicketEmailAutomatically()) {
                            Log::debug(sprintf("SummitOrderService::processTicketData sending invitation email to attendee %s", $attendee->getEmail()));
                            $attendee->sendInvitationEmail($ticket);
                        }
                    }
                }


                if (is_null($ticket)) {
                    Log::warning("SummitOrderService::processTicketData ticket is null stop current row processing.");
                    return;
                }

                Log::debug(sprintf("SummitOrderService::processTicketData - got ticket %s (%s)", $ticket->getId(), $ticket->getNumber()));

                // badge data
                if (!$badge_data_present) {
                    Log::warning("SummitOrderService::processTicketData badge data is not present stop current row processing.");
                    return;
                }

                $badge_type = null;

                if ($reader->hasColumn("badge_type_id")) {
                    Log::debug(sprintf("SummitOrderService::processTicketData trying to get badge type by id %s", $row['badge_type_id']));
                    $badge_type = $summit->getBadgeTypeById(intval($row['badge_type_id']));
                }

                if (is_null($badge_type) && $reader->hasColumn("badge_type_name")) {
                    Log::debug(sprintf("SummitOrderService::processTicketData trying to get badge type by name %s", $row['badge_type_name']));
                    $badge_type = $summit->getBadgeTypeByName(trim($row['badge_type_name']));
                }

                if (!is_null($badge_type))
                    Log::debug(sprintf("SummitOrderService::processTicketData - got badge type %s (%s)", $badge_type->getId(), $badge_type->getName()));

                if (!$ticket->hasBadge()) {
                    // create it
                    if (!is_null($badge_type)) {
                        Log::warning("SummitOrderService::processTicketData badge type is null stop current row processing.");
                        return;
                    }
                    Log::debug(sprintf("SummitOrderService::processTicketData - ticket %s (%s) has not badge ... creating it", $ticket->getId(), $ticket->getNumber()));
                    $badge = SummitBadgeType::buildBadgeFromType($badge_type);
                    $ticket->setBadge($badge);
                }

                $badge = $ticket->getBadge();

                if (!is_null($badge_type))
                    $badge->setType($badge_type);

                $clearedFeatures = false;
                // check if we are setting any badge feature
                Log::debug("SummitOrderService::processTicketData processing badge type features");
                foreach ($summit->getBadgeFeaturesTypes() as $featuresType) {
                    $feature_name = $featuresType->getName();
                    Log::debug(sprintf("SummitOrderService::processTicketData processing badge type feature %s for ticket %s", $feature_name, $ticket->getId()));
                    if (!$reader->hasColumn($feature_name)) {
                        Log::debug(sprintf("SummitOrderService::processTicketData badge type feature %s does not exists as column", $feature_name));
                        continue;
                    }

                    if (!$clearedFeatures) {
                        $badge->clearFeatures();
                        $clearedFeatures = true;
                    }

                    $mustAdd = intval($row[$feature_name]) === 1;
                    if (!$mustAdd) {
                        Log::debug(sprintf("SummitOrderService::processTicketData badge type feature %s not set for ticket %s", $feature_name, $ticket->getId()));
                        continue;
                    }
                    Log::debug(sprintf("SummitOrderService::processTicketData - ticket %s (%s) - trying to add new features to ticket badge (%s)", $ticket->getId(), $ticket->getNumber(), $feature_name));
                    $feature = $summit->getFeatureTypeByName(trim($feature_name));
                    if (is_null($feature)) {
                        Log::warning(sprintf("SummitOrderService::processTicketData feature %s does not exist on summit %s", $feature, $summit->getId()));
                        continue;
                    }
                    Log::debug(sprintf("SummitOrderService::processTicketData badge type feature %s set for ticket %s", $feature_name, $ticket->getId()));
                    $badge->addFeature($feature);
                }
            });
        }

        Log::debug(sprintf("SummitOrderService::processTicketData deleting file %s from storage %s", $path, $this->download_strategy->getDriver()));
        $this->download_strategy->delete($path);
    }

    /**
     * @param Summit $summit
     * @param array $payload
     * @throws ValidationException
     */
    public function ingestExternalTicketData(Summit $summit, array $payload): void
    {

        Log::debug
        (
            sprintf
            (
                "SummitOrderService::ingestExternalTicketData summit %s payload %s",
                $summit->getId(), json_encode($payload)
            )
        );

        $email_to = $payload['email_to'] ?? null;

        if (!$summit->hasDefaultBadgeType()) {
            throw new ValidationException("need to define a default badge type");
        }

        if (empty($summit->getExternalSummitId())) {
            throw new ValidationException("need to set a value for external_summit_id");
        }

        if (empty($summit->getExternalRegistrationFeedType())) {
            throw new ValidationException("need to set a value for external_registration_feed_type");
        }

        if (empty($summit->getExternalRegistrationFeedApiKey())) {
            throw new ValidationException("need to set a value for external_registration_feed_api_key");
        }

        IngestSummitExternalRegistrationData::dispatch
        (
            $summit->getId(),
            $email_to
        );
    }


    public function processAllOrderReminder(): void
    {
        $summits = $this->tx_service->transaction(function () {
            return $this->summit_repository->getNotEnded();
        });

        foreach ($summits as $summit) {
            if ($summit->isRegistrationAllowAutomaticReminderEmails()) {
                Log::debug
                (
                    sprintf
                    (
                        "SummitOrderService::processAllOrderReminder calling processSummitOrderReminders for summit %s",
                        $summit->getId()
                    )
                );
                $this->processSummitOrderReminders($summit);
            } else {
                Log::debug
                (
                    sprintf
                    (
                        "SummitOrderService::processAllOrderReminder summit %s doesn't allow automatic reminder emails",
                        $summit->getId()
                    )
                );
            }
        }
    }

    /**
     * @param Summit $summit
     * @throws \Exception
     */
    public function processSummitOrderReminders(Summit $summit): void
    {

        Log::debug(sprintf("SummitOrderService::processSummitOrderReminders summit %s", $summit->getId()));

        if ($summit->isEnded()) {
            Log::warning(sprintf("SummitOrderService::processSummitOrderReminders - summit %s has ended already", $summit->getId()));
            return;
        }

        $page = 1;
        $has_more_items = true;

        do {
            // done in this way to avoid db lock contention

            $orders = $this->tx_service->transaction(function () use ($summit, $page) {
                return $this->order_repository->getAllOrderThatNeedsEmailActionReminder($summit, new PagingInfo($page, 100));
            });

            $has_more_items = $orders->hasMoreItems();

            foreach ($orders->getItems() as $order) {
                if (!$order instanceof SummitOrder) continue;
                Log::debug(sprintf("SummitOrderService::processSummitOrderReminders - summit %s order %s", $summit->getId(), $order->getId()));

                $order_tickets = $order->getTickets();

                try {
                    //specific case check: don't send order reminder if there is one ticket per order and is the same owner
                    if ($order_tickets->count() != 1 || $order_tickets->first()->getOwnerEmail() !== $order->getOwnerEmail()) {
                        $this->processOrderReminder($order);
                    }
                } catch (\Exception $ex) {
                    Log::error($ex);
                }

                foreach ($order_tickets as $ticket) {
                    try {
                        if (!$ticket->isActive()) {
                            Log::warning(sprintf("SummitOrderService::processSummitOrderReminders - summit %s order %s skipping ticket %s (not active)", $summit->getId(), $order->getId(), $ticket->getId()));
                            continue;
                        }
                        $this->processTicketReminder($ticket);
                    } catch (\Exception $ex) {
                        Log::error($ex);
                    }
                }
            }

            ++$page;

        } while ($has_more_items);
    }

    /**
     * @param SummitOrder $order
     * @throws \Exception
     */
    public function processOrderReminder(SummitOrder $order): void
    {
        $this->tx_service->transaction(function () use ($order) {

            $summit = $order->getSummit();
            if ($summit->isEnded()) {
                Log::warning(sprintf("SummitOrderService::processOrderReminder - summit %s has ended already", $summit->getId()));
                return;
            }

            if (!$order->isPaid()) {
                Log::warning(sprintf("SummitOrderService::processOrderReminder - order %s no need email reminder", $order->getId()));
                return;
            }

            $needs_action = false;

            foreach ($order->getTickets() as $ticket) {
                if (!$ticket->isActive()) {
                    Log::warning(sprintf("SummitOrderService::processOrderReminder - order %s skipping ticket %s ( NOT ACTIVE ).", $order->getId(), $ticket->getId()));
                    continue;
                }
                if (!$ticket->hasOwner()) {
                    $needs_action = true;
                    break;
                }
                $attendee = $ticket->getOwner();
                $attendee->updateStatus();
                if (!$attendee->isComplete()) {
                    $needs_action = true;
                    break;
                }
            }

            if (!$needs_action) {
                Log::warning(sprintf("SummitOrderService::processOrderReminder - order %s no need email reminder", $order->getId()));
                return;
            }

            $last_action_date = $order->getLastReminderEmailSentDate();
            $summit = $order->getSummit();
            $days_interval = $summit->getRegistrationReminderEmailDaysInterval();

            if ($days_interval <= 0) return;
            $utc_now = new \DateTime('now', new \DateTimeZone('UTC'));
            Log::debug(sprintf("SummitOrderService::processOrderReminder - last_action_date %s  utc_now %s", $last_action_date->format("Y-m-d H:i:s"), $utc_now->format("Y-m-d H:i:s")));
            $last_action_date->add(new \DateInterval("P" . $days_interval . 'D'));
            Log::debug(sprintf("SummitOrderService::processOrderReminder - last action date plus %s days %s  utc_now %s", $days_interval, $last_action_date->format("Y-m-d H:i:s"), $utc_now->format("Y-m-d H:i:s")));

            if ($last_action_date <= $utc_now) {

                $order->setLastReminderEmailSentDate($utc_now);
                Log::debug(sprintf("SummitOrderService::processOrderReminder - sending reminder email for order %s", $order->getId()));
                SummitOrderReminderEmail::dispatch($order);
            }
        });
    }

    /**
     * @param SummitAttendeeTicket $ticket
     * @throws \Exception
     */
    public function processTicketReminder(SummitAttendeeTicket $ticket): void
    {
        $this->tx_service->transaction(function () use ($ticket) {

            if (!$ticket->hasOwner()) {
                Log::warning(sprintf("SummitOrderService::processTicketReminder ticket %s no need email reminder ( no owner )", $ticket->getId()));
                return;
            }

            if (!$ticket->isPaid()) {
                Log::warning(sprintf("SummitOrderService::processTicketReminder ticket %s no need email reminder (not paid )", $ticket->getId()));
                return;
            }

            if (!$ticket->hasTicketType()) {
                Log::warning(sprintf("SummitOrderService::processTicketReminder  ticket %s no need email reminder ( no type )", $ticket->getId()));
                return;
            }

            $attendee = $ticket->getOwner();

            if ($attendee->isComplete()) {
                Log::warning(sprintf("SummitOrderService::processTicketReminder  ticket %s no need email reminder", $ticket->getId()));
                return;
            }

            $last_action_date = $attendee->getLastReminderEmailSentDate();
            $order = $ticket->getOrder();
            $summit = $order->getSummit();

            if ($summit->isEnded()) {
                Log::warning(sprintf("SummitOrderService::processTicketReminder - summit %s has ended already", $summit->getId()));
                return;
            }

            $days_interval = $summit->getRegistrationReminderEmailDaysInterval();
            Log::debug(sprintf("SummitOrderService::processTicketReminder days_interval is %s for summit %s", $days_interval, $summit->getId()));
            if ($days_interval <= 0) return;
            $utc_now = new \DateTime('now', new \DateTimeZone('UTC'));
            $last_action_date->add(new \DateInterval("P" . $days_interval . 'D'));
            Log::debug(sprintf("SummitOrderService::processTicketReminder last_action_date %s now %s", $last_action_date->format("Y-m-d H:i:s"), $utc_now->format("Y-m-d H:i:s")));
            if ($last_action_date <= $utc_now) {

                $attendee->setLastReminderEmailSentDate($utc_now);
                Log::debug(sprintf("SummitOrderService::processTicketReminder sending reminder email for ticket %s", $ticket->getId()));
                // regenerate hash
                $ticket->generateHash();
                SummitTicketReminderEmail::dispatch($ticket);
            }
        });
    }

    /**
     * @param Summit $summit
     * @param string $order_hash
     * @return SummitAttendeeTicket|null
     */
    public function getMyTicketByOrderHash(Summit $summit, string $order_hash): ?SummitAttendeeTicket
    {
        return $this->tx_service->transaction(function () use ($summit, $order_hash) {
            $order = $this->order_repository->getByHashLockExclusive($order_hash);

            if (is_null($order) || !$order instanceof SummitOrder || $summit->getId() != $order->getSummitId())
                throw new EntityNotFoundException("order not found");

            if (!$order->isSingleOrder()) {
                throw new ValidationException("order is not single ticket or owner is equal to attendee");
            }

            $ticket = $order->getTickets()->first();
            if (!$ticket instanceof SummitAttendeeTicket) {
                throw new EntityNotFoundException("ticket not found");
            }

            if (!$ticket->canPubliclyEdit()) {
                Log::debug(sprintf("SummitOrderService::getMyTicketByOrderHash regenerating hash for ticket %s", $ticket->getId()));
                $ticket->generateHash();
            }

            return $ticket;
        });
    }

    /**
     * @param SummitOrder $order
     */
    private function sendAttendeesInvitationEmail(SummitOrder $order): void
    {
        Log::debug(sprintf("SummitOrderService::sendAttendeesInvitationEmail order %s", $order->getId()));

        foreach ($order->getTickets() as $ticket) {
            try {
                Log::debug(sprintf("SummitOrderService::sendAttendeesInvitationEmail order %s ticket %s", $order->getId(), $ticket->getNumber()));
                if (!$ticket->hasOwner()) {
                    Log::debug(sprintf("SummitOrderService::sendAttendeesInvitationEmail ticket %s has not owner set", $ticket->getNumber()));
                    continue;
                }
                $attendee = $ticket->getOwner();
                $ticket->generateQRCode();
                $ticket->generateHash();
                $delay = 0;
                if($order->isSingleTicket() && $attendee->getEmail() !== $order->getOwnerEmail()){
                    Log::debug
                    (
                        sprintf
                        (
                            "SummitOrderService::sendAttendeesInvitationEmail ticket %s attendee %s is not the same as order owner %s (SOMEONE_ELSE_FLOW)",
                            $ticket->getId(),
                            $attendee->getEmail(),
                            $order->getOwnerEmail()
                        )
                    );
                    // delay invitation by N Minutes ....
                    $delay = Config::get("registration.attendee_invitation_email_delay", 10);
                }
                Log::debug
                (
                    sprintf
                    (
                        "SummitOrderService::sendAttendeesInvitationEmail ticket %s sending invitation email to attendee %s with delay %s minutes",
                        $ticket->getNumber(),
                        $attendee->getEmail(),
                        $delay
                    )
                );

                SendAttendeeInvitationEmail::dispatch($ticket->getId())->delay(now()->addMinutes($delay));

            } catch (\Exception $ex) {
                Log::warning($ex);
            }
        }
    }

    /**
     * @param SummitOrder $order
     */
    private function sendExistentSummitOrderOwnerEmail(SummitOrder $order)
    {
        Log::debug(sprintf("SummitOrderService::sendExistentSummitOrderOwnerEmail for order %s", $order->getId()));
        RegisteredMemberOrderPaidMail::dispatch($order);
    }

    /**
     * @param SummitOrder $order
     * @param array $user_registration_request
     */
    private function sendSummitOrderOwnerInvitationEmail(SummitOrder $order, array $user_registration_request)
    {
        Log::debug(sprintf("SummitOrderService::sendSummitOrderOwnerInvitationEmail for order %s", $order->getId()));
        UnregisteredMemberOrderPaidMail::dispatch($order, $user_registration_request['set_password_link']);
    }

    /**
     * @param int $orderId
     * @throws \Exception
     */
    public function processOrderPaymentConfirmation(int $orderId): void
    {

        $this->tx_service->transaction(function () use ($orderId) {

            Log::debug(sprintf("SummitOrderService::processOrderPaymentConfirmation: trying to get order id %s", $orderId));

            // lock it and refresh it
            $order = $this->order_repository->getByIdExclusiveLock($orderId, true);

            if (is_null($order) || !$order instanceof SummitOrder) {
                Log::warning(sprintf("SummitOrderService::processOrderPaymentConfirmation: order %s not found.", $orderId));
            }

            $summit = $this->summit_repository->getByIdRefreshed($order->getSummitId());

            $shouldSendOrderEmail = $summit->isRegistrationSendOrderEmailAutomatically();
            $shouldSendTicketEmail = $summit->isRegistrationSendTicketEmailAutomatically();

            Log::debug(
                sprintf
                (
                    "SummitOrderService::processOrderPaymentConfirmation: got order id %s nbr %s fname %s lname %s email %s shouldSendOrderEmail %b shouldSendTicketEmail %b",
                    $orderId,
                    $order->getNumber(),
                    $order->getOwnerFirstName(),
                    $order->getOwnerSurname(),
                    $order->getOwnerEmail(),
                    $shouldSendOrderEmail,
                    $shouldSendTicketEmail
                )
            );

            $order->generateQRCode();

            if (!$order->hasOwner()) {
                // owner is not registered ...
                Log::debug("SummitOrderService::processOrderPaymentConfirmation: order has not owner set");
                $ownerEmail = $order->getOwnerEmail();
                // check if we have a member on db
                Log::debug(sprintf("SummitOrderService::processOrderPaymentConfirmation: trying to get email %s from db", $ownerEmail));
                $member = $this->member_repository->getByEmail($ownerEmail);

                if (!is_null($member)) {
                    // its turns out that email was registered as a member
                    // set the owner and move on
                    Log::debug(sprintf("SummitOrderService::processOrderPaymentConfirmation: member %s found at db", $ownerEmail));
                    $order->setOwner($member);

                    // send email to owner;
                    if ($shouldSendOrderEmail && !$order->isOfflineOrder()) {
                        Log::debug
                        (
                            sprintf
                            (
                            "SummitOrderService::processOrderPaymentConfirmation: order %s sending email to owner %s %s (%s)",
                                $order->getId(),
                                $order->getOwnerFirstName(),
                                $order->getOwnerSurname(),
                                $order->getOwnerEmail()
                            )
                        );
                        $this->sendExistentSummitOrderOwnerEmail($order);
                    }

                    if ($shouldSendTicketEmail && !$order->isOfflineOrder()) {
                        Log::debug("SummitOrderService::processOrderPaymentConfirmation: sending email to attendees");
                        $this->sendAttendeesInvitationEmail($order);
                    }

                    $this->processInvitation($order);

                    return;
                }

                $user = $this->member_service->checkExternalUser($ownerEmail);

                if (is_null($user)) {

                    Log::debug
                    (
                        sprintf
                        (
                            "SummitOrderService::processOrderPaymentConfirmation - user %s does not exist at IDP, emiting a registration request on idp",
                            $ownerEmail
                        )
                    );

                    // user does not exists , emit a registration request
                    // need to send email with set password link
                    if ($shouldSendOrderEmail && !$order->isOfflineOrder()) {
                        Log::debug("SummitOrderService::processOrderPaymentConfirmation - sending email to owner (NON REGISTERED)");
                        $this->sendSummitOrderOwnerInvitationEmail($order, $this->member_service->emitRegistrationRequest
                        (
                            $ownerEmail,
                            $order->getOwnerFirstName(),
                            $order->getOwnerSurname(),
                            $order->getOwnerCompanyName()
                        ));
                    }

                    if ($shouldSendTicketEmail && !$order->isOfflineOrder()) {
                        Log::debug("SummitOrderService::processOrderPaymentConfirmation - sending email to attendees");
                        $this->sendAttendeesInvitationEmail($order);
                    }

                    $this->processInvitation($order);

                    return;
                }

                Log::debug
                (
                    sprintf
                    (
                        "SummitOrderService::processOrderPaymentConfirmation - Creating a local user for %s",
                        $ownerEmail
                    )
                );

                // we have an user on idp

                $external_id = $user['id'];

                try {
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
                    // race condition lost, try to get it
                    Log::warning($ex);
                    $member = $this->member_repository->getByExternalIdExclusiveLock(intval($external_id));
                    $order = $this->order_repository->getByIdExclusiveLock($orderId);
                }
                // add the order to newly created member
                $member->addSummitRegistrationOrder($order);
            }

            if ($shouldSendOrderEmail && !$order->isOfflineOrder()) {
                Log::debug
                (
                    sprintf
                    (
                        "SummitOrderService::processOrderPaymentConfirmation: order %s sending email to owner %s %s (%s) (REGISTERED)",
                        $order->getId(),
                        $order->getOwnerFirstName(),
                        $order->getOwnerSurname(),
                        $order->getOwnerEmail()
                    )
                );
                $this->sendExistentSummitOrderOwnerEmail($order);
            }

            if ($shouldSendTicketEmail && !$order->isOfflineOrder()) {
                Log::debug("SummitOrderService::processOrderPaymentConfirmation - sending email to attendees");
                $this->sendAttendeesInvitationEmail($order);
            }

            $this->processInvitation($order);
        });
    }

    /**
     * @param SummitOrder $order
     * @return SummitOrder
     * @throws ValidationException
     */
    private function processInvitation(SummitOrder $order): SummitOrder
    {
        $summit = $order->getSummit();
        // we should mark the associated invitation as processed
        Log::debug
        (
            sprintf
            (
                "SummitOrderService::processInvitation: trying to get invitation for email %s order %s.",
                $order->getOwnerEmail(),
                $order->getId()
            )
        );

        $invitation = $summit->getSummitRegistrationInvitationByEmail($order->getOwnerEmail());

        if (is_null($invitation) || $invitation->isAccepted()) {
            Log::debug(sprintf("SummitOrderService::processInvitation invitation for email %s does not exists or its already accepted.", $order->getOwnerEmail()));
            return $order;
        }
        $invitation->addOrder($order);
        Log::debug(sprintf("SummitOrderService::processInvitation trying mark invitation for email %s as accepted.", $order->getOwnerEmail()));
        $invitation->markAsAccepted();

        $attendee = $this->attendee_repository->getBySummitAndEmail($summit, $order->getOwnerEmail());

        if (!is_null($attendee)) {
            CopyInvitationTagsToAttendees::dispatch($summit->getId(), $invitation->getId(), $attendee->getId());
        }

        return $order;
    }

    /**
     * @inheritDoc
     */
    public function copyInvitationTagsToAttendee(int $summit_id, int $invitation_id, int $attendee_id):void {
        $this->tx_service->transaction(function () use ($summit_id, $invitation_id, $attendee_id) {
            $summit = $this->summit_repository->getById($summit_id);
            if (!$summit instanceof Summit)
                throw new EntityNotFoundException();

            $attendee = $summit->getAttendeeById($attendee_id);
            if (!$attendee instanceof SummitAttendee)
                throw new EntityNotFoundException();

            $invitation = $summit->getSummitRegistrationInvitationById($invitation_id);
            if (!$invitation instanceof SummitRegistrationInvitation)
                throw new EntityNotFoundException();

            foreach ($invitation->getTags() as $tag) {
                $attendee->addTag($tag);
            }
        });
    }

    /**
     * @inheritDoc
     */
    public function activateTicket(Summit $summit, int $order_id, int $ticket_id): SummitAttendeeTicket
    {
        return $this->tx_service->transaction(function () use ($summit, $order_id, $ticket_id) {
            // lock and get the order
            $order = $this->order_repository->getByIdExclusiveLock($order_id);

            if (is_null($order) || !$order instanceof SummitOrder)
                throw new EntityNotFoundException("order not found");

            $ticket = $order->getTicketById($ticket_id);

            if (is_null($ticket))
                throw new EntityNotFoundException("ticket not found");

            $ticket->activate();

            $owner = $ticket->getOwner();

            if (!is_null($owner) && $summit->isRegistrationSendTicketEmailAutomatically())
                $owner->sendInvitationEmail($ticket);

            return $ticket;
        });
    }

    /**
     * @inheritDoc
     */
    public function deActivateTicket(Summit $summit, int $order_id, int $ticket_id): SummitAttendeeTicket
    {
        return $this->tx_service->transaction(function () use ($summit, $order_id, $ticket_id) {
            // lock and get the order
            $order = $this->order_repository->getByIdExclusiveLock($order_id);

            if (is_null($order) || !$order instanceof SummitOrder)
                throw new EntityNotFoundException("order not found");

            $ticket = $order->getTicketById($ticket_id);

            if (is_null($ticket))
                throw new EntityNotFoundException("ticket not found");

            $ticket->deActivate();

            $owner = $ticket->getOwner();

            if (!is_null($owner))
                $owner->sendRevocationTicketEmail($ticket);

            return $ticket;
        });
    }
}
