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

}