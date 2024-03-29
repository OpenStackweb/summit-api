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

use App\Models\Foundation\Summit\Factories\SummitTicketTypeFactory;
use App\Models\Foundation\Summit\Registration\PromoCodes\Strategies\PromoCodeAllowedTicketTypesStrategyFactory;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use libs\utils\ITransactionService;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use models\main\Member;
use models\summit\PrePaidSummitRegistrationDiscountCode;
use models\summit\PrePaidSummitRegistrationPromoCode;
use models\summit\Summit;
use models\summit\SummitRegistrationPromoCode;
use models\summit\SummitTicketType;
use models\summit\SummitTicketTypePrePaid;
use models\summit\SummitTicketTypeWithPromo;
use services\apis\IEventbriteAPI;
use utils\Filter;

/**
 * Class SummitTicketTypeService
 * @package App\Services\Model
 */
final class SummitTicketTypeService
    extends AbstractService
    implements ISummitTicketTypeService
{

    /**
     * @var IEventbriteAPI
     */
    private $eventbrite_api;

    /**
     * SummitTicketTypeService constructor.
     * @param IEventbriteAPI $eventbrite_api
     * @param ITransactionService $tx_service
     */
    public function __construct
    (
        IEventbriteAPI      $eventbrite_api,
        ITransactionService $tx_service
    )
    {
        parent::__construct($tx_service);
        $this->eventbrite_api = $eventbrite_api;
    }

    /**
     * @param Summit $summit
     * @param array $data
     * @return array
     * @throws EntityNotFoundException
     */
    static private function getTicketTypeParams(Summit $summit, array $data): array
    {
        if (isset($data['badge_type_id'])) {
            $badge_type = $summit->getBadgeTypeById(intval($data['badge_type_id']));
            if (is_null($badge_type))
                throw new EntityNotFoundException(sprintf("badge_type_id %s not found", $data['badge_type_id']));
            $data['badge_type'] = $badge_type;
        }
        return $data;
    }

    /**
     * @param Summit $summit
     * @param array $data
     * @return SummitTicketType
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function addTicketType(Summit $summit, array $data)
    {
        $ticket_type = $this->tx_service->transaction(function () use ($summit, $data) {

            $former_ticket_type = $summit->getTicketTypeByName(trim($data['name']));

            if (!is_null($former_ticket_type)) {
                throw new ValidationException
                (
                    trans
                    (
                        'validation_errors.SummitTicketTypeService.addTicketType.NameAlreadyExists'
                    ),
                    [
                        'name' => trim($data['name']),
                        'summit_id' => $summit->getId()
                    ]
                );
            }

            if (isset($data['external_id'])) {
                $former_ticket_type = $summit->getTicketTypeByExternalId(trim($data['external_id']));
                if (!is_null($former_ticket_type)) {
                    throw new ValidationException
                    (
                        trans
                        (
                            'validation_errors.SummitTicketTypeService.addTicketType.ExternalIdAlreadyExists'
                        ),
                        [
                            'external_id' => trim($data['external_id']),
                            'summit_id' => $summit->getId()
                        ]
                    );
                }
            }

            $ticket_type = SummitTicketTypeFactory::build($summit, self::getTicketTypeParams($summit, $data));

            if ($summit->hasTicketTypes()) {
                // before add check if we have the same currency
                $currency = $ticket_type->getCurrency();
                $summit_currency = $summit->getDefaultTicketTypeCurrency();
                if (!empty($currency) && !empty($summit_currency) && $summit_currency != $currency)
                    throw new ValidationException(sprintf("Ticket type should have same currency as summit (%s).", $summit_currency));
            }

            $summit->addTicketType($ticket_type);
            return $ticket_type;
        });

        return $ticket_type;

    }

    /**
     * @param Summit $summit
     * @param int $ticket_type_id
     * @param array $data
     * @return SummitTicketType
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function updateTicketType(Summit $summit, $ticket_type_id, array $data)
    {
        return $this->tx_service->transaction(function () use ($summit, $ticket_type_id, $data) {

            if (isset($data['name'])) {
                $former_ticket_type = $summit->getTicketTypeByName(trim($data['name']));

                if (!is_null($former_ticket_type) && $former_ticket_type->getId() != $ticket_type_id) {
                    throw new ValidationException
                    (
                        trans
                        (
                            'validation_errors.SummitTicketTypeService.updateTicketType.NameAlreadyExists'
                        ),
                        [
                            'name' => trim($data['name']),
                            'summit_id' => $summit->getId()
                        ]
                    );
                }
            }

            if (isset($data['external_id'])) {
                $former_ticket_type = $summit->getTicketTypeByExternalId(trim($data['external_id']));
                if (!is_null($former_ticket_type) && $former_ticket_type->getId() != $ticket_type_id) {
                    throw new ValidationException
                    (
                        trans
                        (
                            'validation_errors.SummitTicketTypeService.updateTicketType.ExternalIdAlreadyExists'
                        ),
                        [
                            'external_id' => trim($data['external_id']),
                            'summit_id' => $summit->getId()
                        ]
                    );
                }
            }

            $ticket_type = $summit->getTicketTypeById($ticket_type_id);

            if (is_null($ticket_type)) {
                throw new EntityNotFoundException
                (
                    trans
                    (
                        'not_found_errors.SummitTicketTypeService.updateTicketType.TicketTypeNotFound',
                        [
                            'ticket_type_id' => $ticket_type_id,
                            'summit_id' => $summit->getId()
                        ]
                    )
                );
            }

            $summit_currency = $summit->getDefaultTicketTypeCurrency();
            if (isset($data['currency'])) {
                $currency = trim($data['currency']);
                if (!empty($currency) && !empty($summit_currency) && $summit_currency != $currency)
                    throw new ValidationException(sprintf("Ticket type should have same currency as summit (%s).", $summit_currency));
            }

            return SummitTicketTypeFactory::populate($ticket_type, self::getTicketTypeParams($summit, $data));
        });
    }

    /**
     * @param Summit $summit
     * @param int $ticket_type_id
     * @return SummitTicketType
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function deleteTicketType(Summit $summit, $ticket_type_id)
    {
        return $this->tx_service->transaction(function () use ($summit, $ticket_type_id) {

            $ticket_type = $summit->getTicketTypeById($ticket_type_id);

            if (is_null($ticket_type)) {
                throw new EntityNotFoundException
                (
                    trans
                    (
                        'not_found_errors.SummitTicketTypeService.deleteTicketType.TicketTypeNotFound',
                        [
                            'ticket_type_id' => $ticket_type_id,
                            'summit_id' => $summit->getId()
                        ]
                    )
                );
            }

            $summit->removeTicketType($ticket_type);
        });
    }

    /**
     * @param Summit $summit
     * @return SummitTicketType[]
     * @throws ValidationException
     */
    public function seedSummitTicketTypesFromEventBrite(Summit $summit)
    {
        return $this->tx_service->transaction(function () use ($summit) {

            $external_summit_id = $summit->getExternalSummitId();

            if (empty($external_summit_id)) {
                throw new ValidationException
                (
                    trans
                    (
                        'validation_errors.SummitTicketTypeService.seedSummitTicketTypesFromEventBrite.MissingExternalId',
                        [
                            'summit_id' => $summit->getId()
                        ]
                    )
                );
            }

            $apiFeedKey = $summit->getExternalRegistrationFeedApiKey();

            if (empty($apiFeedKey)) {
                throw new ValidationException(sprintf("external_registration_feed_api_key is empty for summit %s", $summit->getId()));
            }

            $this->eventbrite_api->setCredentials([
                'token' => $apiFeedKey
            ]);

            $has_more_items = true;
            $page = 1;
            $res = [];

            do {

                $response = $this->eventbrite_api->getTicketTypes($summit);

                $has_more_items = $response->hasMoreItems();

                foreach ($response as $ticket_class) {

                    Log::debug(sprintf("SummitTicketTypeService::seedSummitTicketTypesFromEventBrite external ticket class %s", json_encode($ticket_class)));

                    $id = $ticket_class['id'];
                    $old_ticket_type = $summit->getTicketTypeByExternalId($id);

                    if (!is_null($old_ticket_type)) {

                        $old_ticket_type->setName(trim($ticket_class['name']));
                        $old_ticket_type->setDescription(isset($ticket_class['description']) ? trim($ticket_class['description']) : '');
                        if (isset($ticket_class['capacity']))
                            $old_ticket_type->setQuantity2Sell(intval($ticket_class['capacity']));
                        if (isset($ticket_class['cost']) && !is_null($ticket_class['cost']))
                            $old_ticket_type->setCost(floatval($ticket_class['cost']['major_value']));
                        continue;
                    }

                    $new_ticket_type = new SummitTicketType();
                    $new_ticket_type->setExternalId($id);
                    $new_ticket_type->setName($ticket_class['name']);
                    $new_ticket_type->setDescription(isset($ticket_class['description']) ? trim($ticket_class['description']) : '');

                    if (isset($ticket_class['capacity']))
                        $new_ticket_type->setQuantity2Sell(intval($ticket_class['capacity']));
                    if (isset($ticket_class['cost']) && !is_null($ticket_class['cost']))
                        $new_ticket_type->setCost(floatval($ticket_class['cost']['major_value']));

                    $summit->addTicketType($new_ticket_type);
                    $res[] = $new_ticket_type;
                }

                foreach ($res as $ticket_type) {
                    Event::dispatch
                    (
                        new SummitTicketTypeInserted
                        (
                            $ticket_type->getId(),
                            $ticket_type->getSummitId()
                        )
                    );
                }

                ++$page;
            } while ($has_more_items);

            return $res;
        });
    }

    /**
     * @param Summit $summit
     * @param Member $member
     * @param string|null $promocode_val
     * @return SummitTicketType[]
     * @throws \Exception
     */
    public function getAllowedTicketTypes(Summit $summit, Member $member, ?string $promocode_val = null): array
    {
        return $this->tx_service->transaction(function () use ($summit, $member, $promocode_val) {

            Log::debug
            (
                sprintf
                (
                    "SummitTicketTypeService::getAllowedTicketTypes summit %s member %s promo code %s.",
                    $summit->getId(),
                    $member->getId(),
                    !empty($promocode_val) ? $promocode_val : 'NONE'
                )
            );

            $promo_code = null;
            $factory = new PromoCodeAllowedTicketTypesStrategyFactory();

            if (!empty($promocode_val)) {
                Log::debug
                (
                    sprintf
                    (
                        "SummitTicketTypeService::getAllowedTicketTypes trying to get promocode_val %s from summit %s",
                        $promocode_val,
                        $summit->getId()
                    )
                );
                $promo_code = $summit->getPromoCodeByCode($promocode_val);
                Log::debug
                (
                    sprintf
                    (
                        "SummitTicketTypeService::getAllowedTicketTypes got promo code %s",
                        !is_null($promo_code) ? $promo_code->getCode() : "NONE"
                    )
                );
            }

            return $factory->build($summit, $member, $promo_code)->getTicketTypes();
        });
    }

    /**
     * @param Summit $summit
     * @param String $currency_symbol
     * @return void
     * @throws \Exception
     */
    public function updateCurrencySymbol(Summit $summit, string $currency_symbol): void {
        $this->tx_service->transaction(function () use ($summit, $currency_symbol) {
            Log::debug
            (
                sprintf
                (
                    "SummitTicketTypeService::updateCurrencySymbol summit %s new currency symbol %s.",
                    $summit->getId(),
                    $currency_symbol
                )
            );

            foreach($summit->getTicketTypes() as $tt) {
                $tt->setCurrency($currency_symbol);
            }
        });
    }
}