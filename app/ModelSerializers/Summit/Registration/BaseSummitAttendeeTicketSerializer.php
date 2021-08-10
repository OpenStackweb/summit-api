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

use Illuminate\Support\Facades\Log;
use Libs\ModelSerializers\AbstractSerializer;
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
        'Active'             => 'is_active:json_bool',
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
    public function serialize($expand = null, array $fields = array(), array $relations = array(), array $params = array())
    {
        $ticket = $this->object;
        if (!$ticket instanceof SummitAttendeeTicket) return [];
        $values   = parent::serialize($expand, $fields, $relations, $params);

        if (!count($relations)) $relations = $this->getAllowedRelations();

        Log::debug(sprintf("BaseSummitAttendeeTicketSerializer::serialize  expand %s", $expand));

        if (in_array('applied_taxes', $relations)) {
            $applied_taxes = [];
            foreach ($ticket->getAppliedTaxes() as $tax) {
                $applied_taxes[] = $tax->getId();
            }
            $values['applied_taxes'] = $applied_taxes;
        }

        if (!empty($expand)) {
            $exp_expand = explode(',', $expand);
            foreach ($exp_expand as $relation) {
                switch (trim($relation)) {
                    case 'ticket_type': {
                        if(!$ticket->hasTicketType()) break;
                        unset($values['ticket_type_id']);
                        $values['ticket_type'] = SerializerRegistry::getInstance()->getSerializer($ticket->getTicketType())->serialize(AbstractSerializer::getExpandForPrefix('ticket_type', $expand));
                    }
                        break;
                    case 'badge': {
                        if(!$ticket->hasBadge()) break;
                        unset($values['badge_id']);
                        $values['badge'] = SerializerRegistry::getInstance()->getSerializer($ticket->getBadge())->serialize(AbstractSerializer::getExpandForPrefix('badge', $expand));
                    }
                        break;
                    case 'promo_code': {
                        if(!$ticket->hasPromoCode()) break;
                        unset($values['promo_code_id']);
                        $values['promo_code'] = SerializerRegistry::getInstance()->getSerializer($ticket->getPromoCode())->serialize(AbstractSerializer::getExpandForPrefix('promo_code', $expand));
                    }
                        break;
                    case 'applied_taxes': {
                        if (in_array('applied_taxes', $relations)) {
                            unset( $values['applied_taxes']);
                            $applied_taxes = [];
                            foreach ($ticket->getAppliedTaxes() as $tax) {
                                $applied_taxes[] = SerializerRegistry::getInstance()->getSerializer($tax)->serialize(AbstractSerializer::getExpandForPrefix('applied_taxes', $expand));
                            }
                            $values['applied_taxes'] = $applied_taxes;
                        }
                    }
                    break;
                    case 'owner': {
                        if(!$ticket->hasOwner()) break;
                        unset($values['owner_id']);
                        $values['owner'] = SerializerRegistry::getInstance()->getSerializer($ticket->getOwner())->serialize(AbstractSerializer::getExpandForPrefix('owner', $expand));
                    }
                        break;
                }

            }
        }
        return $values;
    }
}