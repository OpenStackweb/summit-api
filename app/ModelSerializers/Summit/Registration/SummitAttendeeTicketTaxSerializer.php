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
use Libs\ModelSerializers\AbstractSerializer;
use models\summit\SummitAttendeeTicketTax;
/**
 * Class SummitAttendeeTicketTaxSerializer
 * @package ModelSerializers
 */
final class SummitAttendeeTicketTaxSerializer extends AbstractSerializer
{
    protected static $array_mappings = [
        'Amount'   => 'amount:json_float',
        'TaxId'    => 'tax_id:json_int',
        'TicketId' => 'ticket_id:json_int',
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
        $tax_applied = $this->object;
        if (!$tax_applied instanceof SummitAttendeeTicketTax) return [];
        $values = parent::serialize($expand, $fields, $relations, $params);
        if (!count($relations)) $relations = $this->getAllowedRelations();

        if (!empty($expand)) {
            $exp_expand = explode(',', $expand);
            foreach ($exp_expand as $relation) {
                switch (trim($relation)) {
                    case 'tax':
                        {
                            if ($tax_applied->hasTax()) {
                                unset($values['tax_id']);
                                $values['tax'] = SerializerRegistry::getInstance()->getSerializer($tax_applied->getTax())->serialize($expand);
                            }
                        }
                        break;
                    case 'ticket':
                        {
                            if ($tax_applied->hasTicket()) {
                                unset($values['ticket_id']);
                                $values['ticket'] = SerializerRegistry::getInstance()->getSerializer($tax_applied->getTicket())->serialize($expand);
                            }
                        }
                        break;
                }

            }
        }
        return $values;
    }

}