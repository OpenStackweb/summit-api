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
use ModelSerializers\SilverStripeSerializer;

class RSVPCSVSerializer extends SilverStripeSerializer
{
    protected static $array_mappings = [
        'OwnerId'             => 'owner_id:json_int',
        'EventId'             => 'event_id:json_int',
        'SeatType'            => 'seat_type:json_string',
        'Created'             => 'created:datetime_epoch',
        'ConfirmationNumber'  => 'confirmation_number:json_string',
        'ActionSource'        => 'action_source:json_string',
        'ActionDate'          => 'action_date:datetime_epoch',
        'Status'              => 'status:json_string',
    ];
}