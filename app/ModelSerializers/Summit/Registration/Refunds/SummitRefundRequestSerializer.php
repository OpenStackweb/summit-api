<?php namespace App\ModelSerializers\Summit\Registration\Refunds;
/**
 * Copyright 2021 OpenStack Foundation
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

use Libs\ModelSerializers\Many2OneExpandSerializer;
use Libs\ModelSerializers\One2ManyExpandSerializer;
use models\summit\SummitRefundRequest;
use ModelSerializers\SilverStripeSerializer;

/**
 * Class SummitRefundRequestSerializer
 * @package App\ModelSerializers\Summit\Registration\Refunds
 */
class SummitRefundRequestSerializer extends SilverStripeSerializer
{
    protected static $array_mappings = [
        'Status' => 'status:json_string',
        'RequestedById' => 'requested_by_id:json_int',
        'ActionById' => 'action_by_id:json_int',
        'ActionDate' => 'action_date:datetime_epoch',
        'RefundedAmount' => 'refunded_amount:json_money',
        'RefundedAmountInCents' => 'refunded_amount_in_cents:json_int',
        'TaxesRefundedAmount' => 'taxes_refunded_amount:json_money',
        'TaxesRefundedAmountInCents' => 'taxes_refunded_amount_in_cents:json_int',
        'TotalRefundedAmount' => 'total_refunded_amount:json_money',
        'TotalRefundedAmountInCents' => 'total_refunded_amount_in_cents:json_int',
        'Notes' => 'notes:json_string',
        'PaymentGatewayResult' => 'payment_gateway_result:json_string',
    ];

    protected static $allowed_relations = [
        'requested_by',
        'refunded_taxes',
        'action_by',
    ];

    protected static $expand_mappings = [
        'requested_by' => [
            'type' => One2ManyExpandSerializer::class,
            'original_attribute' => 'requested_by_id',
            'getter' => 'getRequestedBy',
            'has' => 'hasRequestedBy'
        ],
        'action_by' => [
            'type' => One2ManyExpandSerializer::class,
            'original_attribute' => 'action_by_id',
            'getter' => 'getActionBy',
            'has' => 'hasActionBy'
        ],
        'refunded_taxes' => [
            'type' => Many2OneExpandSerializer::class,
            'getter' => 'getRefundedTaxes',
        ],
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
        $request = $this->object;
        if (!$request instanceof SummitRefundRequest) return [];
        $values = parent::serialize($expand, $fields, $relations, $params);

        if (in_array('refunded_taxes', $relations) && !isset($values['refunded_taxes'])) {
            $refunded_taxes = [];
            foreach ($request->getRefundedTaxes() as $refund_tax) {
                $refunded_taxes[] = $refund_tax->getId();
            }
            $values['refunded_taxes'] = $refunded_taxes;
        }

        return $values;
    }
}