<?php namespace ModelSerializers;
/**
 * Copyright 2020 OpenStack Foundation
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
use models\summit\SummitRegistrationInvitation;
/**
 * Class SummitRegistrationInvitationCSVSerializer
 * @package ModelSerializers
 */
class SummitRegistrationInvitationCSVSerializer extends SilverStripeSerializer
{
    protected static $array_mappings = [
        'MemberId' => 'member_id:json_int',
        'OrderId' => 'order_id:json_int',
        'SummitId' => 'summit_id:json_int',
        'FirstName' => 'first_name:json_string',
        'LastName' => 'last_name:json_string',
        'Email' => 'email:json_string',
        'Accepted' => 'is_accepted:jon_boolean',
        'Sent' => 'is_sent:jon_boolean',
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
        $invitation = $this->object;
        if (!$invitation instanceof SummitRegistrationInvitation) return [];

        if (!count($relations)) $relations = $this->getAllowedRelations();
        $values  = parent::serialize($expand, $fields, $relations, $params);

        $allowed_ticket_types = [];
        foreach ($invitation->getTicketTypes() as $ticket_type){
            $allowed_ticket_types[] = $ticket_type->getId();
        }
        $values['allowed_ticket_types'] = $allowed_ticket_types;

        return $values;
    }

}