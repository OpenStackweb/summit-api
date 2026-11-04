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
use Libs\ModelSerializers\One2ManyExpandSerializer;

/**
 * Class SummitAttendeeCheckInLogSerializer
 * @package ModelSerializers
 */
class SummitAttendeeCheckInLogSerializer extends SilverStripeSerializer
{
    protected static $array_mappings = [
        'Action'     => 'action:json_string',
        'Source'     => 'source:json_string',
        'Reason'     => 'reason:json_string',
        'ClientId'   => 'client_id:json_string',
        'IpAddress'  => 'ip_address:json_string',
        'UserAgent'  => 'user_agent:json_string',
        'AttendeeId' => 'attendee_id:json_int',
        'ActorId'    => 'actor_id:json_int',
    ];

    protected static $expand_mappings = [
        'actor' => [
            'type' => One2ManyExpandSerializer::class,
            'original_attribute' => 'actor_id',
            'getter' => 'getActor',
            'has' => 'hasActor',
            'serializer_type' => SerializerRegistry::SerializerType_Admin,
        ],
    ];
}
