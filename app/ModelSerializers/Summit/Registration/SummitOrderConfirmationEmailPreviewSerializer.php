<?php namespace ModelSerializers;
use Libs\ModelSerializers\AbstractSerializer;
use models\summit\SummitOrder;

/**
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


/**
 * Class SummitOrderConfirmationEmailPreviewSerializer
 * @package App\ModelSerializers
 */
final class SummitOrderConfirmationEmailPreviewSerializer extends AbstractSerializer
{
    protected static $array_mappings = [
        'CreditCardType' => 'order_credit_card_type:json_string',
        'CreditCard4Number' => 'order_credit_card_4number:json_string',
        'Currency' => 'order_currency:json_string',
        'CurrencySymbol' => 'order_currency_symbol:json_string',
        'Number' => 'order_number:json_string',
        'FinalAmountAdjusted' => 'order_amount:json_float',
        'OwnerFullName' => 'owner_full_name:json_string',
        'OwnerCompanyName' => 'owner_company:json_string',
    ];

    protected static $allowed_relations = [
        'member',
        'tickets',
    ];

    /**
     * @param null $expand
     * @param array $fields
     * @param array $relations
     * @param array $params
     * @return array
     */
    public function serialize($expand = null, array $fields = array(), array $relations = array(), array $params = array())
    {
        $order = $this->object;
        if (!$order instanceof SummitOrder) return [];
        $values = parent::serialize($expand, $fields, $relations, $params);

        $values["summit_name"] = $order->getSummit()->getName();

        if (!count($relations)) $relations = $this->getAllowedRelations();

        if (in_array('tickets', $relations)) {
            $tickets = [];
            foreach ($order->getTickets() as $ticket) {
                $ticket_dic = [
                    "currency"         => $ticket->getCurrency(),
                    "currency_symbol"  => $ticket->getCurrencySymbol(),
                    "has_owner"        => $ticket->hasOwner(),
                    "need_details"     => false,
                    "ticket_type_name" => $ticket->getTicketTypeName(),
                    "owner_email"      => $ticket->getOwnerEmail(),
                    "price"            => $ticket->getFinalAmount()
                ];

                $promo_code = $ticket->getPromoCode();
                if (!is_null($promo_code)) {
                    $ticket_dic["promo_code"] = ["code" => $promo_code->getCode()];
                }

                $tickets[] = $ticket_dic;
            }
            $values['tickets'] = $tickets;
        }

        return $values;
    }
}