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

use Illuminate\Support\Facades\Log;
use models\summit\ISummitTicketType;
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
    /**
     * @param SummitTicketType $type
     * @return ISummitTicketType
     */
    private function applyPromo2TicketType(SummitTicketType $type): ISummitTicketType {
        return new SummitTicketTypePrePaid($type, $this->promo_code);
    }

    /**
     * @inheritDoc
     */
    public function getTicketTypes(): array
    {
        $regular_ticket_types = parent::getTicketTypes();

        Log::debug(
            sprintf(
                "PrePaidPromoCodeTicketTypesStrategy::getTicketTypes applying promocode %s to ticket types",
                $this->promo_code->getCode()
            )
        );

        $unassigned_tickets = $this->promo_code->getUnassignedTickets();
        $prepaid_ticket_types = array();
        foreach ($unassigned_tickets as $unassigned_ticket) {
            if (!$unassigned_ticket->getOrder()->isOfflineOrder()) continue;

            $type = $unassigned_ticket->getTicketType();
            if (!array_key_exists($type->getId(), $prepaid_ticket_types)) {
                $prepaid_ticket_types[$type->getId()] = $this->applyPromo2TicketType($type);
            }
        }
        return array_merge($regular_ticket_types, array_values($prepaid_ticket_types));
    }
}