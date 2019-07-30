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
use App\Models\Foundation\Summit\Factories\SummitOrderFactory;
use App\Models\Foundation\Summit\Factories\SummitPromoCodeFactory;
use App\Models\Foundation\Summit\Factories\SummitTicketTypeFactory;
use App\Models\Foundation\Summit\Repositories\ISummitOrderRepository;
use App\Services\Apis\ExternalRegistrationFeeds\IExternalRegistrationFeedFactory;
use App\Services\Model\AbstractService;
use App\Services\Model\IRegistrationIngestionService;
use libs\utils\ITransactionService;
use models\exceptions\ValidationException;
use models\main\IMemberRepository;
use models\summit\factories\SummitAttendeeFactory;
use models\summit\ISummitAttendeeTicketRepository;
use models\summit\ISummitRepository;
use models\summit\Summit;
use Exception;
use Illuminate\Support\Facades\Log;
use models\summit\SummitAttendeeBadge;
use models\summit\SummitAttendeeTicket;
use models\summit\SummitRegistrationDiscountCode;
use models\summit\ISummitAttendeeRepository;
use models\summit\SummitRegistrationPromoCode;
/**
 * Class RegistrationIngestionService
 * @package App\Services\Model\Imp
 */
final class RegistrationIngestionService
    extends AbstractService implements IRegistrationIngestionService
{

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
     * RegistrationIngestionService constructor.
     * @param ISummitRepository $summit_repository
     * @param IExternalRegistrationFeedFactory $feed_factory
     * @param ISummitOrderRepository $order_repository
     * @param ISummitAttendeeTicketRepository $ticket_repository
     * @param IMemberRepository $member_repository
     * @param ISummitAttendeeRepository $attendee_repository
     * @param ITransactionService $tx_service
     */
    public function __construct
    (
        ISummitRepository $summit_repository,
        IExternalRegistrationFeedFactory $feed_factory,
        ISummitOrderRepository $order_repository,
        ISummitAttendeeTicketRepository $ticket_repository,
        IMemberRepository $member_repository,
        ISummitAttendeeRepository $attendee_repository,
        ITransactionService $tx_service
    )
    {
        parent::__construct($tx_service);
        $this->summit_repository = $summit_repository;
        $this->feed_factory = $feed_factory;
        $this->order_repository = $order_repository;
        $this->ticket_repository = $ticket_repository;
        $this->member_repository = $member_repository;
        $this->attendee_repository = $attendee_repository;
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
            Log::debug(sprintf("RegistrationIngestionService::ingestSummit: ingesting summit %s", $summit_id));
            $feed = $this->feed_factory->build($summit);

            if (is_null($feed))
                throw new ValidationException("invalid feed");

            if (!$summit->hasDefaultBadgeType())
                throw new ValidationException(sprintf("summit %s has not default badge type set", $summit_id));

            do {
                Log::debug(sprintf("RegistrationIngestionService::ingestSummit: getting external attendees page %s", $page));
                $response = $feed->getAttendees($page);
                if (!$response->hasData()) return;
                $has_more_items = $response->hasMoreItems();

                foreach ($response as $index => $external_attendee) {

                    $this->tx_service->transaction(function () use ($summit_id, $external_attendee) {
                        $summit = $this->summit_repository->getById($summit_id);
                        if (!$summit instanceof Summit) return;
                        $default_badge_type = $summit->getDefaultBadgeType();

                        if (!$summit instanceof Summit) return;
                        $external_attendee_profile = $external_attendee['profile'];
                        $external_promo_code = $external_attendee['promotional_code'];
                        $ticket_class = $external_attendee['ticket_class'];
                        $external_order = $external_attendee['order'];
                        $refunded = $external_attendee['refunded'];
                        $cancelled = $external_attendee['cancelled'];

                        Log::debug(sprintf("RegistrationIngestionService::ingestSummit: proccessing external attendee %s - external order id %s", $external_attendee['id'], $external_order['id']));

                        $ticket_type = $summit->getTicketTypeByExternalId($ticket_class['id']);
                        if (is_null($ticket_type)) {
                            // create ticket type if does not exists
                            Log::debug(sprintf("RegistrationIngestionService::ingestSummit: ticket class %s does not exists", $ticket_class['id']));
                            $ticket_type = SummitTicketTypeFactory::build(
                                $summit, [
                                    'name' => $ticket_class['name'],
                                    'description' => $ticket_class['description'],
                                    'external_id' => $ticket_class['id'],
                                    'cost' => $ticket_class['cost']['major_value'],
                                    'currency' => $ticket_class['cost']['currency'],
                                    'quantity_2_sell' => $ticket_class['quantity_total'],
                                ]
                            );

                            $summit->addTicketType($ticket_type);
                        }

                        $order = $this->order_repository->getByExternalIdAndSummitLockExclusive($summit, $external_order['id']);
                        if (is_null($order)) {
                            Log::debug(sprintf("RegistrationIngestionService::ingestSummit: order %s does not exists", $external_order['id']));
                            $order = SummitOrderFactory::build($summit, [
                                'external_id' => $external_order['id'],
                                'owner_first_name' => $external_order['first_name'],
                                'owner_last_name' => $external_order['last_name'],
                                'owner_email' => $external_order['email'],
                            ]);

                            $order->setSummit($summit);

                            $order->generateNumber();

                            $owner = $this->member_repository->getByEmail($external_order['email']);
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

                        $ticket = $this->ticket_repository->getBySummitAndExternalOrderIdAndExternalAttendeeIdExclusiveLock
                        (
                            $summit,
                            $external_order['id'],
                            $external_attendee['id']
                        );

                        if (is_null($ticket)) {

                            Log::debug(sprintf("RegistrationIngestionService::ingestSummit: ticket %s - %s does not exists", $external_order['id'], $external_attendee['id']));
                            $ticket = new SummitAttendeeTicket();
                            $ticket->setExternalAttendeeId($external_attendee['id']);
                            $ticket->setExternalOrderId($external_order['id']);
                            $ticket->setBoughtDate(new \DateTime($external_attendee['created'], new \DateTimeZone('UTC')));
                            $ticket->setOrder($order);
                            $ticket->generateNumber();

                            do {

                                if (!$this->ticket_repository->existNumber($ticket->getNumber()))
                                    break;
                                $ticket->generateNumber();
                            } while (1);

                            $ticket->setTicketType($ticket_type);
                        }

                        if (count($external_promo_code)) {
                            // has promo code
                            $promo_code = $summit->getPromoCodeByCode($external_promo_code['code']);
                            if (is_null($promo_code)) {

                                Log::debug(sprintf("RegistrationIngestionService::ingestSummit: promo code %s - %s does not exists", $external_promo_code['id'], $external_promo_code['code']));

                                $promo_code_params = [
                                    'class_name' => $external_promo_code['promotion_type'] == 'discount' ? SummitRegistrationDiscountCode::ClassName : SummitRegistrationPromoCode::ClassName,
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

                        // default badge
                        if (!$ticket->hasBadge()) {
                            $badge = new SummitAttendeeBadge();
                            $badge->setType($default_badge_type);
                            $ticket->setBadge($badge);
                        }

                        // assign attendee
                        // check if we have already an attendee on this summit
                        $attendee_email = trim($external_attendee_profile['email']);
                        $first_name     = trim($external_attendee_profile['first_name']);
                        $last_name      = trim($external_attendee_profile['last_name']);
                        $company        = isset($external_attendee_profile['company']) ? trim($external_attendee_profile['company']) : '';
                        Log::debug(sprintf("RegistrationIngestionService::ingestSummit: looking for attendee %s , %s (%s)", $first_name, $last_name, $attendee_email));
                        $attendee = $this->attendee_repository->getBySummitAndEmailAndFirstNameAndLastNameAndExternalId($summit, $attendee_email, $first_name, $last_name, $external_attendee['id']);

                        if (is_null($attendee)) {
                            Log::debug(sprintf("RegistrationIngestionService::ingestSummit: attendee %s does not exists", $attendee_email));
                            $attendee = SummitAttendeeFactory::build($summit, [
                                'external_id' => $external_attendee['id'],
                                'first_name'  => $first_name,
                                'last_name'   => $last_name,
                                'company'     => $company,
                                'email'       => $attendee_email
                            ], $this->member_repository->getByEmail($attendee_email));

                            $summit->addAttendee($attendee);
                        } else {
                            SummitAttendeeFactory::populate($summit, $attendee, [
                                'external_id' => $external_attendee['id'],
                                'first_name'  => $first_name,
                                'last_name'   => $last_name,
                                'company'     => $company,
                                'email'       => $attendee_email
                            ]);
                        }

                        $ticket->setOwner($attendee);
                        if (!$cancelled && !$refunded) {
                            $ticket->setPaid();
                            $order->setPaidStatus();
                        }
                        if ($cancelled) {
                            $ticket->setCancelled();
                        }
                        if ($refunded) {
                            $ticket->setRefunded();
                        }

                        $order->addTicket($ticket);
                        $ticket->generateQRCode();
                        $ticket->generateHash();
                    });
                }

                ++$page;
            } while ($has_more_items);

            $end = time();
            $delta = $end - $start;
            log::debug(sprintf("RegistrationIngestionService::ingestSummit execution call %s seconds - summit %s", $delta, $summit_id));
        } catch (Exception $ex) {
            Log::warning(sprintf("error external feed for summit id %s", $summit->getId()));
            Log::warning($ex);
        }
    }
}