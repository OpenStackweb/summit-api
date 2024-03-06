<?php namespace ModelSerializers;
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
use models\summit\SummitOrder;
use libs\utils\JsonUtils;
/**
 * Class SummitOrderReservationSerializer
 * @package App\ModelSerializers
 */
class SummitOrderReservationSerializer extends SummitOrderBaseSerializer
{
    protected static $array_mappings = [
        'RawAmount' => 'raw_amount:json_money',
        'RawAmountInCents' => 'raw_amount_in_cents:json_int',
        'FinalAmount' => 'amount:json_money',
        'FinalAmountInCents' => 'amount_in_cents:json_int',
        'TaxesAmount' => 'taxes_amount:json_money',
        'TaxesAmountInCents' => 'taxes_amount_in_cents:json_int',
        'DiscountAmount' => 'discount_amount:json_money',
        'DiscountRate' => 'discount_rate:json_float',
        'DiscountAmountInCents' => 'discount_amount_in_cents:json_int',
        'PaymentGatewayClientToken' => 'payment_gateway_client_token:json_string',
        'PaymentGatewayCartId' => 'payment_gateway_cart_id:json_string',
        'Hash' => 'hash:json_string',
        'HashCreationDate' => 'hash_creation_date:datetime_epoch',
        'RefundedAmount' => 'refunded_amount:json_money',
        'RefundedAmountInCents' => 'refunded_amount_in_cents:json_int',
        'CreditCardType' => 'credit_card_type:json_string',
        'CreditCard4Number' => 'credit_card_4number:json_string',
    ];

    protected static $allowed_relations = [
        'applied_taxes',
    ];

    /**
     * @param null $expand
     * @param array $fields
     * @param array $relations
     * @param array $params
     * @return array
     */
    public function serialize($expand = null, array $fields = [], array $relations = [], array $params = [])
    {
        if (!count($relations)) $relations = $this->getAllowedRelations();
        $order = $this->object;
        if (!$order instanceof SummitOrder) return [];
        $values = parent::serialize($expand, $fields, $relations, $params);
        if (in_array('applied_taxes', $relations)) {
            $applied_taxes = $order->getAppliedTaxes();

            for($i = 0 ; $i < count($applied_taxes) ; $i++)
            {
                $applied_taxes[$i]['amount'] = JsonUtils::toJsonMoney($applied_taxes[$i]['amount']);
            }

            $values['applied_taxes'] = $applied_taxes;
        }
        return $values;
    }
}
