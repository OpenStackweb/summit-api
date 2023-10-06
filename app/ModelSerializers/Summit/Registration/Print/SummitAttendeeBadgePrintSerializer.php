<?php namespace ModelSerializers;
/*
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
 * Class SummitAttendeeBadgePrintSerializer
 * @package ModelSerializers
 */
final class SummitAttendeeBadgePrintSerializer extends SilverStripeSerializer
{
    protected static $array_mappings = [
        'PrintDate'           => 'print_date:datetime_epoch',
        'RequestorId'         => 'requestor_id:json_int',
        'BadgeId'             => 'badge_id:json_int',
        'ViewTypeId'          => 'view_type_id:json_int',
        'ViewTypeName'        => 'view_type_name:json_string',
    ];

    protected static $expand_mappings = [
        'requestor' => [
            'type' => One2ManyExpandSerializer::class,
            'original_attribute' => 'requestor_id',
            'getter' => 'getRequestor',
            'has' => 'hasRequestor'
        ],
        'badge' => [
            'type' => One2ManyExpandSerializer::class,
            'original_attribute' => 'badge_id',
            'getter' => 'getBadge',
            'has' => 'hasBadge'
        ],
        'view_type' => [
            'type' => One2ManyExpandSerializer::class,
            'original_attribute' => 'view_type_id',
            'getter' => 'getViewType',
            'has' => 'hasViewType'
        ],
    ];
}