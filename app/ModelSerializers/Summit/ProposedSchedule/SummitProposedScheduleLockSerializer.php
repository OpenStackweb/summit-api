<?php namespace App\ModelSerializers\Summit\ProposedSchedule;
/**
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

use Libs\ModelSerializers\One2ManyExpandSerializer;
use ModelSerializers\SilverStripeSerializer;

/**
 * Class SummitProposedScheduleLockSerializer
 * @package App\ModelSerializers\Summit
 */
final class SummitProposedScheduleLockSerializer extends SilverStripeSerializer
{
    protected static $array_mappings = [
        'Reason' => 'reason:json_string',
        'CreatedById' => 'created_by_id:json_int',
        'TrackId' => 'track_id:json_int',
    ];

    protected static $expand_mappings = [
        'track' => [
            'type' => One2ManyExpandSerializer::class,
            'original_attribute' => 'track_id',
            'getter' => 'getTrack',
            'has' => 'hasTrack'
        ],
        'created_by' => [
            'type' => One2ManyExpandSerializer::class,
            'original_attribute' => 'created_by_id',
            'getter' => 'getCreatedBy',
            'has' => 'hasCreatedBy'
        ],
    ];
}