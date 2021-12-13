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
use Libs\ModelSerializers\Many2OneExpandSerializer;
use models\summit\SummitRegistrationInvitation;
/**
 * Class SummitRegistrationInvitationSerializer
 * @package ModelSerializers
 */
class SummitRegistrationInvitationSerializer extends SilverStripeSerializer
{
    protected static $array_mappings = [
        'Email' => 'email:json_string',
        'FirstName' => 'first_name:json_string',
        'LastName' => 'last_name:json_string',
        'SummitId' => 'summit_id:json_int',
        'Accepted' => 'is_accepted:json_boolean',
        'Sent'     => 'is_sent:json_boolean',
        'AcceptedDate' => 'accepted_date:datetime_epoch',
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

    protected static $expand_mappings = [
        'allowed_ticket_types' => [
            'type' => Many2OneExpandSerializer::class,
            'getter' => 'getTicketTypes',
        ]
    ];


}