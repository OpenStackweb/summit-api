<?php namespace ModelSerializers;
/**
 * Copyright 2026 OpenStack Foundation
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

use models\summit\DomainAuthorizedSummitRegistrationDiscountCode;

/**
 * Class DomainAuthorizedSummitRegistrationDiscountCodeSerializer
 * @package ModelSerializers
 */
class DomainAuthorizedSummitRegistrationDiscountCodeSerializer
    extends SummitRegistrationDiscountCodeSerializer
{
    protected static $array_mappings = [
        'AllowedEmailDomains'  => 'allowed_email_domains:json_string_array',
        'QuantityPerAccount'   => 'quantity_per_account:json_int',
        'AutoApply'            => 'auto_apply:json_boolean',
    ];

    protected static $allowed_relations = [
        'allowed_ticket_types',
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
        if (!$code instanceof DomainAuthorizedSummitRegistrationDiscountCode) return [];
        $values = parent::serialize($expand, $fields, $relations, $params);

        // RE-ADD allowed_ticket_types (parent discount serializer unsets it).
        // Check both relations (default serialization) and expand (explicit ?expand= request).
        $needs_allowed_ticket_types = in_array('allowed_ticket_types', $relations)
            || (!empty($expand) && str_contains($expand, 'allowed_ticket_types'));
        if ($needs_allowed_ticket_types && !isset($values['allowed_ticket_types'])) {
            $ticket_types = [];
            foreach ($code->getAllowedTicketTypes() as $ticket_type) {
                $ticket_types[] = $ticket_type->getId();
            }
            $values['allowed_ticket_types'] = $ticket_types;
        }

        // Transient remaining_quantity_per_account (set by service layer)
        $values['remaining_quantity_per_account'] = $code->getRemainingQuantityPerAccount();

        return $values;
    }

    protected static $expand_mappings = [
        'allowed_ticket_types' => [
            'type' => \Libs\ModelSerializers\Many2OneExpandSerializer::class,
            'getter' => 'getAllowedTicketTypes',
        ],
    ];
}
