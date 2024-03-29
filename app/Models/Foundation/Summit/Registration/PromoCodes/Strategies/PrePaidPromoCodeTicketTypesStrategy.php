<?php namespace App\Models\Foundation\Summit\Registration\PromoCodes\Strategies;
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

use App\Models\Foundation\Summit\Registration\PromoCodes\PromoCodesUtils;
use Illuminate\Support\Facades\Log;
use models\exceptions\ValidationException;
use models\main\Member;
use models\summit\ISummitTicketType;
use models\summit\PrePaidSummitRegistrationDiscountCode;
use models\summit\PrePaidSummitRegistrationPromoCode;
use models\summit\Summit;
use models\summit\SummitRegistrationPromoCode;
use models\summit\SummitTicketType;
use models\summit\SummitTicketTypePrePaid;

/**
 * Class PrePaidPromoCodeTicketTypesStrategy
 * @package App\Models\Foundation\Summit\Registration\PromoCodes\Strategies
 */
class PrePaidPromoCodeTicketTypesStrategy
    extends RegularPromoCodeTicketTypesStrategy
    implements IPromoCodeAllowedTicketTypesStrategy
{
    private $prepaid_promo_code;
    /**
     * @param Summit $summit
     * @param Member $member
     * @param SummitRegistrationPromoCode|null $promo_code
     */
    public function __construct(Summit $summit, Member $member, ?SummitRegistrationPromoCode $promo_code)
    {
        if(!PromoCodesUtils::isPrePaidPromoCode( $promo_code ))
            throw new ValidationException("You need to provide a pre paid promo code!");

        Log::debug
        (
            sprintf
            (
                "PrePaidPromoCodeTicketTypesStrategy::build summit %s member %s promo code %s",
                $summit->getId(),
                $member->getId(),
                !is_null($promo_code) ? $promo_code->getCode() : 'NONE'
            )
        );
       parent::__construct($summit, $member, null);
       $this->prepaid_promo_code = $promo_code;
    }
    /**
     * @param SummitTicketType $type
     * @return ISummitTicketType
     */
    private function applyPrePaidPromo2TicketType(SummitTicketType $type): ISummitTicketType {
        Log::debug
        (
            sprintf
            (
                "PrePaidPromoCodeTicketTypesStrategy::applyPrePaidPromo2TicketType applying prepaid_promocode %s to ticket type %s",
                !is_null($this->prepaid_promo_code) ? $this->prepaid_promo_code->getCode(): 'NONE',
                $type->getId()
            )
        );
        return new SummitTicketTypePrePaid($type, $this->prepaid_promo_code);
    }

    /**
     * @inheritDoc
     */
    public function getTicketTypes(): array
    {
        $regular_ticket_types = parent::getTicketTypes();

        Log::debug(
            sprintf(
                "PrePaidPromoCodeTicketTypesStrategy::getTicketTypes applying prepaid promocode %s to ticket types",
                $this->prepaid_promo_code->getCode()
            )
        );

        $unassigned_tickets = $this->prepaid_promo_code->getUnassignedTickets();
        $prepaid_ticket_types = [];
        foreach ($unassigned_tickets as $unassigned_ticket) {
            if (!$unassigned_ticket->getOrder()->isOfflineOrder()) continue;

            $type = $unassigned_ticket->getTicketType();
            if (!array_key_exists($type->getId(), $prepaid_ticket_types)) {
                $prepaid_ticket_types[$type->getId()] = $this->applyPrePaidPromo2TicketType($type);
            }
        }
        return array_merge($regular_ticket_types, array_values($prepaid_ticket_types));
    }
}