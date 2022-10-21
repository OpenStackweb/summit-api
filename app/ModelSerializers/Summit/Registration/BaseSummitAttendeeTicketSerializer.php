<?php namespace ModelSerializers;
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

use App\ModelSerializers\Traits\RequestScopedCache;
use Libs\ModelSerializers\Many2OneExpandSerializer;
use Libs\ModelSerializers\One2ManyExpandSerializer;
use models\summit\SummitAttendeeTicket;
/**
 * Class BaseSummitAttendeeTicketSerializer
 * @package ModelSerializers
 */
class BaseSummitAttendeeTicketSerializer extends SilverStripeSerializer
{
    protected static $array_mappings = [
        'Number'             => 'number:json_string',
        'Status'             => 'status:json_string',
        'ExternalOrderId'    => 'external_order_id:json_string',
        'ExternalAttendeeId' => 'external_attendee_id:json_string',
        'BoughtDate'         => 'bought_date:datetime_epoch',
        'TicketTypeId'       => 'ticket_type_id:json_int',
        'OwnerId'            => 'owner_id:json_int',
        'OrderId'            => 'order_id:json_int',
        'BadgeId'            => 'badge_id:json_int',
        'PromoCodeId'        => 'promo_code_id:json_int',
        'RawCost'            => 'raw_cost:json_float',
        'FinalAmount'        => 'final_amount:json_float',
        'Discount'           => 'discount:json_float',
        'RefundedAmount'     => 'refunded_amount:json_float',
        'Currency'           => 'currency:json_string',
        'TaxesAmount'        => 'taxes_amount:json_float',
        'Active'             => 'is_active:json_bool',
    ];

    protected static $allowed_relations = [
        'applied_taxes',
        'refund_requests',
    ];

    protected static $expand_mappings = [
        'ticket_type' => [
            'type' => One2ManyExpandSerializer::class,
            'original_attribute' => 'ticket_type_id',
            'getter' => 'getTicketType',
            'has' => 'hasTicketType'
        ],
        'badge' => [
            'type' => One2ManyExpandSerializer::class,
            'original_attribute' => 'badge_id',
            'getter' => 'getBadge',
            'has' => 'hasBadge'
        ],
        'promo_code' => [
            'type' => One2ManyExpandSerializer::class,
            'original_attribute' => 'promo_code_id',
            'getter' => 'getPromoCode',
            'has' => 'hasPromoCode'
        ],
        'owner' => [
            'type' => One2ManyExpandSerializer::class,
            'original_attribute' => 'owner_id',
            'getter' => 'getOwner',
            'has' => 'hasOwner'
        ],
        'refund_requests' => [
            'type' => Many2OneExpandSerializer::class,
            'getter' => 'getRefundedRequests',
        ],
        'applied_taxes' => [
            'type' => Many2OneExpandSerializer::class,
            'getter' => 'getAppliedTaxes',
        ],
        'order' => [
            'type' => One2ManyExpandSerializer::class,
            'original_attribute' => 'order_id',
            'getter' => 'getOrder',
            'has' => 'hasOrder'
        ],
    ];

    use RequestScopedCache;

    /**
     * @param null $expand
     * @param array $fields
     * @param array $relations
     * @param array $params
     * @return array
     */
    public function serialize($expand = null, array $fields = array(), array $relations = array(), array $params = array())
    {
        return $this->cache(
            $this->getRequestKey
            (
                "BaseSummitAttendeeTicketSerializer",
                $this->object->getIdentifier(),
                $expand,
                $fields,
                $relations
            ), function () use ($expand, $fields, $relations, $params) {

            if (!count($relations)) $relations = $this->getAllowedRelations();
            $ticket = $this->object;
            if (!$ticket instanceof SummitAttendeeTicket) return [];
            $values = parent::serialize($expand, $fields, $relations, $params);

            if (in_array('applied_taxes', $relations) && !isset($values['applied_taxes'])) {
                $applied_taxes = [];
                foreach ($ticket->getAppliedTaxes() as $tax) {
                    $applied_taxes[] = $tax->getId();
                }
                $values['applied_taxes'] = $applied_taxes;
            }

            if (in_array('refund_requests', $relations) && !isset($values['refund_requests'])) {
                $refund_requests = [];
                foreach ($ticket->getRefundedRequests() as $request) {
                    $refund_requests[] = $request->getId();
                }
                $values['refund_requests'] = $refund_requests;
            }

            return $values;
        });
    }
}