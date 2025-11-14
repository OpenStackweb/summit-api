<?php namespace App\ModelSerializers\Summit;

use Libs\ModelSerializers\One2ManyExpandSerializer;
use ModelSerializers\SilverStripeSerializer;

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

/**
 * Class SummitSponsorshipAddOnSerializer
 * @package ModelSerializers
 */
final class SummitSponsorshipAddOnSerializer extends SilverStripeSerializer
{
    protected static $array_mappings = [
        'Name' => 'name:json_string',
        'Type' => 'type:json_string',
        'SponsorshipId' => 'sponsorship_id:json_int',
    ];

    protected static $expand_mappings = [
        'sponsorship' => [
            'type' => One2ManyExpandSerializer::class,
            'original_attribute' => 'sponsorship_id',
            'getter' => 'getSponsorship',
            'has' => 'hasSponsorship'
        ],
    ];
}
