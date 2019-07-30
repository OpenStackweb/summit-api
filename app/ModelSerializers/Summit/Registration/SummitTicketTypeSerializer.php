<?php namespace ModelSerializers;
/**
 * Copyright 2016 OpenStack Foundation
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
use Libs\ModelSerializers\AbstractSerializer;
use models\summit\SummitTicketType;
/**
 * Class SummitTicketTypeSerializer
 * @package ModelSerializers
 */
final class SummitTicketTypeSerializer extends SilverStripeSerializer
{
    protected static $array_mappings = [
        'Name'                => 'name:json_string',
        'Description'         => 'description:json_string',
        'ExternalId'          => 'external_id:json_string',
        'SummitId'            => 'summit_id:json_int',
        'Cost'                => 'cost:json_float',
        'Currency'            => 'currency:json_string',
        'Quantity2Sell'       => 'quantity_2_sell:json_int',
        'MaxQuantityPerOrder' => 'max_quantity_per_order:json_int',
        'SalesStartDate'      => 'sales_start_date:datetime_epoch',
        'SalesEndDate'        => 'sales_end_date:datetime_epoch',
        'BadgeTypeId'         => 'badge_type_id:json_int',
        'QuantitySold'        => 'quantity_sold:json_int',
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
    public function serialize($expand = null, array $fields = array(), array $relations = array(), array $params = array() )
    {
        $ticket_type = $this->object;
        if (!$ticket_type instanceof SummitTicketType) return [];
        $values = parent::serialize($expand, $fields, $relations, $params);

        // applied_taxes
        if(in_array('applied_taxes', $relations)) {
            $applied_taxes = [];
            foreach ($ticket_type->getAppliedTaxes() as $tax) {
                $applied_taxes[] = $tax->getId();
            }
            $values['applied_taxes'] = $applied_taxes;
        }

        if (!empty($expand)) {
            $exp_expand = explode(',', $expand);
            foreach ($exp_expand as $relation) {
                switch (trim($relation)) {
                    case 'applied_taxes': {
                        unset($values['applied_taxes']);
                        $applied_taxes = [];
                        foreach ($ticket_type->getAppliedTaxes() as $tax) {
                            $applied_taxes[] = SerializerRegistry::getInstance()->getSerializer($tax)->serialize(AbstractSerializer::filterExpandByPrefix($expand, "applied_taxes"));
                        }
                        $values['applied_taxes'] = $applied_taxes;
                    }
                    break;

                    case 'badge_type': {
                        if($ticket_type->hasBadgeType()) {
                            unset($values['badge_type_id']);
                            $values['badge_type'] = SerializerRegistry::getInstance()->getSerializer($ticket_type->getBadgeType())->serialize(AbstractSerializer::filterExpandByPrefix($expand, "badge_type"));
                        }
                    }
                        break;
                }

            }
        }
        return $values;
    }
}