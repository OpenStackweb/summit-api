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
use models\summit\SummitTaxType;
/**
 * Class SummitTicketTypeSerializer
 * @package ModelSerializers
 */
final class SummitTaxTypeSerializer extends SilverStripeSerializer
{
    protected static $array_mappings = [
        'Name'     => 'name:json_string',
        'TaxId'    => 'tax_id:json_string',
        'Rate'     => 'rate:json_float',
        'SummitId' => 'summit_id:json_int',
    ];

    protected static $allowed_relations = [
        'ticket_types',
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
        $tax = $this->object;
        if (!$tax instanceof SummitTaxType) return [];
        $values = parent::serialize($expand, $fields, $relations, $params);
        // applied_taxes
        if(in_array('ticket_types', $relations)) {
            $ticket_types = [];
            foreach ($tax->getTicketTypes() as $ticket_type) {
                $ticket_types[] = $ticket_type->getId();
            }
            $values['ticket_types'] = $ticket_types;
        }

        if (!empty($expand)) {
            $exp_expand = explode(',', $expand);
            foreach ($exp_expand as $relation) {
                switch (trim($relation)) {
                    case 'ticket_types': {
                        unset($values['ticket_types']);
                        $ticket_types = [];
                        foreach ($tax->getTicketTypes() as $ticket_type) {
                            $ticket_types[] = SerializerRegistry::getInstance()->getSerializer($ticket_type)->serialize(
                                AbstractSerializer::filterExpandByPrefix($expand, $relation),
                                AbstractSerializer::filterFieldsByPrefix($fields, $relation),
                                AbstractSerializer::filterFieldsByPrefix($relations, $relation),
                                $params
                            );
                        }
                        $values['ticket_types'] = $ticket_types;
                    }
                        break;
                }

            }
        }
        return $values;
    }
}