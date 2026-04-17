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
use models\summit\SummitRegistrationDiscountCode;

/**
 * Class SummitRegistrationDiscountCodeSerializer
 * @package ModelSerializers
 */
class SummitRegistrationDiscountCodeSerializer extends SummitRegistrationPromoCodeSerializer
{
    protected static $array_mappings = [
        'Rate' => 'rate:json_float',
        'Amount' => 'amount:json_float',
    ];

    protected static $allowed_relations = [
        'ticket_types_rules',
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
        $code = $this->object;
        if (!$code instanceof SummitRegistrationDiscountCode) return [];
        $values = parent::serialize($expand, $fields, $relations, $params);

        // Discount codes express ticket-type coverage via `ticket_types_rules`
        // (which carries rate/amount per type), so the grandparent's flat
        // `allowed_ticket_types` is redundant and ambiguous for a generic
        // discount code. Subclasses that DO need the flat list (e.g., admin
        // widgets that gate on ticket-type selection without rate semantics)
        // must call self::restoreAllowedTicketTypes() after parent::serialize().
        unset($values['allowed_ticket_types']);

        if (in_array('ticket_types_rules', $relations)) {
            $ticket_types_rules = [];
            foreach ($code->getTicketTypesRules() as $ticket_types_rule) {
                $ticket_types_rules[] = $ticket_types_rule->getId();
            }
            $values['ticket_types_rules'] = $ticket_types_rules;
        }

        if (!empty($expand)) {
            foreach (explode(',', $expand) as $relation) {
                $relation = trim($relation);
                switch ($relation) {
                    case 'ticket_types_rules':
                        {
                            unset($values['ticket_types_rules']);
                            $ticket_types_rules = [];
                            foreach ($code->getTicketTypesRules() as $ticket_types_rule) {
                                $ticket_types_rules[] = SerializerRegistry::getInstance()->getSerializer($ticket_types_rule)->serialize
                                (
                                    AbstractSerializer::filterExpandByPrefix($expand, $relation),
                                    AbstractSerializer::filterFieldsByPrefix($fields, $relation),
                                    AbstractSerializer::filterFieldsByPrefix($relations, $relation),
                                    $params
                                );
                            }
                            $values['ticket_types_rules'] = $ticket_types_rules;
                        }
                        break;

                }
            }
        }

        return $values;
    }

    /**
     * Re-adds the flat `allowed_ticket_types` field (unset by this class on
     * purpose) for subclasses that need it in addition to `ticket_types_rules`.
     * Call after parent::serialize() in the subclass.
     */
    protected function restoreAllowedTicketTypes(array &$values, $expand, array $relations): void
    {
        $code = $this->object;
        if (!$code instanceof SummitRegistrationDiscountCode) return;

        $needs = in_array('allowed_ticket_types', $relations)
            || (!empty($expand) && str_contains($expand, 'allowed_ticket_types'));

        if ($needs && !isset($values['allowed_ticket_types'])) {
            $ticket_types = [];
            foreach ($code->getAllowedTicketTypes() as $ticket_type) {
                $ticket_types[] = $ticket_type->getId();
            }
            $values['allowed_ticket_types'] = $ticket_types;
        }
    }
}