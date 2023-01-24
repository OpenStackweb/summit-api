<?php namespace ModelSerializers;

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

/**
 * Class SummitProposedScheduleSummitEventSerializer
 * @package App\ModelSerializers\Summit
 */
final class SummitProposedScheduleSummitEventSerializer extends SilverStripeSerializer
{
    protected static $array_mappings = [
        'StartDate' => 'start_date:datetime_epoch',
        'EndDate' => 'end_date:datetime_epoch',
        'Duration' => 'duration:json_int',
        'SummitEventId' => 'summit_event_id:json_int',
        'LocationId' => 'location_id:json_int',
        'CreatedById' => 'created_by_id:json_int',
        'UpdatedById' => 'updated_by_id:json_int',
    ];

    protected static $expand_mappings = [
        'summit_event' => [
            'type' => One2ManyExpandSerializer::class,
            'original_attribute' => 'summit_event_id',
            'getter' => 'getSummitEvent',
            'has' => 'hasSummitEvent'
        ],
        'location' => [
            'type' => One2ManyExpandSerializer::class,
            'original_attribute' => 'location_id',
            'getter' => 'getLocation',
            'has' => 'hasLocation'
        ],
        'created_by' => [
            'type' => One2ManyExpandSerializer::class,
            'original_attribute' => 'created_by_id',
            'getter' => 'getCreatedBy',
            'has' => 'hasCreatedBy'
        ],
        'updated_by' => [
            'type' => One2ManyExpandSerializer::class,
            'original_attribute' => 'updated_by_id',
            'getter' => 'getUpdatedBy',
            'has' => 'hasUpdatedBy'
        ],
    ];
}