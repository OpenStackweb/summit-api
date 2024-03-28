<?php namespace ModelSerializers;
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
use Libs\ModelSerializers\AbstractSerializer;
use models\summit\ISummitTicketType;

/**
 * Trait SummitTicketTypeCommonSerializer
 */
trait SummitTicketTypeCommonSerializer
{
    /**
     * @param ISummitTicketType $ticket_type
     * @param array $values
     * @param null $expand
     * @param array $fields
     * @param array $relations
     * @param array $params
     * @return array
     */
    public static function serializeCommonFields(
        ISummitTicketType $ticket_type,
        array $values = array(),
        $expand = null,
        array $fields = array(),
        array $relations = array(),
        array $params = array()): array
    {
        // applied_taxes
        if (in_array('applied_taxes', $relations)) {
            $applied_taxes = [];
            foreach ($ticket_type->getAppliedTaxes() as $tax) {
                $applied_taxes[] = $tax->getId();
            }
            $values['applied_taxes'] = $applied_taxes;
        }

        $values["sub_type"] = $ticket_type->getSubType();

        if (!empty($expand)) {
            $exp_expand = explode(',', $expand);
            foreach ($exp_expand as $relation) {
                $relation = trim($relation);
                switch ($relation) {
                    case 'applied_taxes':
                        {
                            unset($values['applied_taxes']);
                            $applied_taxes = [];
                            foreach ($ticket_type->getAppliedTaxes() as $tax) {
                                $applied_taxes[] = SerializerRegistry::getInstance()->getSerializer($tax)->serialize
                                (
                                    AbstractSerializer::filterExpandByPrefix($expand, $relation),
                                    AbstractSerializer::filterFieldsByPrefix($fields, $relation),
                                    AbstractSerializer::filterFieldsByPrefix($relations, $relation),
                                    $params
                                );
                            }
                            $values['applied_taxes'] = $applied_taxes;
                        }
                        break;

                    case 'badge_type':
                        {
                            if ($ticket_type->hasBadgeType()) {
                                unset($values['badge_type_id']);
                                $values['badge_type'] = SerializerRegistry::getInstance()->getSerializer($ticket_type->getBadgeType())->serialize
                                (
                                    AbstractSerializer::filterExpandByPrefix($expand, $relation),
                                    AbstractSerializer::filterFieldsByPrefix($fields, $relation),
                                    AbstractSerializer::filterFieldsByPrefix($relations, $relation),
                                    $params
                                );
                            }
                        }
                        break;
                }

            }
        }
        return $values;
    }
}