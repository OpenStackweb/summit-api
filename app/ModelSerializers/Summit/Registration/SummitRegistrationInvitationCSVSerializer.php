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
        'Status' => 'status:json_string',
        'Sent' => 'is_sent:json_boolean',
    ];

    protected static $allowed_relations = [
        'allowed_ticket_types',
        'tags',
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
        $invitation = $this->object;
        if (!$invitation instanceof SummitRegistrationInvitation) return [];

        $values  = parent::serialize($expand, $fields, $relations, $params);

        if(in_array('allowed_ticket_types', $relations) && !isset($values['allowed_ticket_types'])){
            $allowed_ticket_types = [];
            foreach ($invitation->getTicketTypes() as $ticket_type){
                $allowed_ticket_types[] = $ticket_type->getName();
            }
            $values['allowed_ticket_types'] = implode('|', $allowed_ticket_types);
        }

        if(in_array('tags', $relations) && !isset($values['tags'])){
            $tags = [];
            foreach ($invitation->getTags() as $tag){
                $tags[] = $tag->getTag();
            }
            $values['tags'] = implode('|', $tags);
        }

        return $values;
    }
}