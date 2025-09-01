<?php namespace App\ModelSerializers\Summit\RSVP;
/**
 * Copyright 2025 OpenStack Foundation
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
use ModelSerializers\SilverStripeSerializer;

class RSVPInvitationSerializer extends SilverStripeSerializer
{
    protected static $array_mappings = [
        'InviteeId' => 'invitee_id:json_int',
        'EventId' => 'event_id:json_int',
        'RSVPId' => 'rsvp_id:json_int',
        'Status' => 'status:json_string',
        'Accepted' => 'is_accepted:json_boolean',
        'Sent'     => 'is_sent:json_boolean',
        'SentDate' => 'sent_date:datetime_epoch',
        'ActionDate' => 'action_date:datetime_epoch',
    ];

    protected static $expand_mappings = [
        'invitee' => [
            'type' => One2ManyExpandSerializer::class,
            'original_attribute' => 'invitee_id',
            'getter' => 'getInvitee',
            'has' => 'hasInvitee'
        ],
        'event' => [
            'type' => One2ManyExpandSerializer::class,
            'original_attribute' => 'event_id',
            'getter' => 'getEvent',
            'has' => 'hasEvent'
        ],
        'rsvp' => [
            'type' => One2ManyExpandSerializer::class,
            'original_attribute' => 'rsvp_id',
            'getter' => 'getRSVP',
            'has' => 'hasRSVP'
        ],
    ];
}