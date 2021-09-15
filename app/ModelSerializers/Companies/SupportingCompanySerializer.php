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
use Libs\ModelSerializers\One2ManyExpandSerializer;
/**
 * Class SupportingCompanySerializer
 * @package ModelSerializers
 */
final class SupportingCompanySerializer extends SilverStripeSerializer
{
    protected static $array_mappings = [
        'CompanyId' => 'company_id:json_int',
        'SponsorshipId' => 'sponsorship_type_id:json_int',
        'Order' => 'order:json_int',
    ];

    protected static $expand_mappings = [
        'company' => [
            'type' => One2ManyExpandSerializer::class,
            'original_attribute' => 'company_id',
            'getter' => 'getCompany',
            'has' => 'hasCompany'
        ],
        'sponsorship_type' => [
            'type' => One2ManyExpandSerializer::class,
            'original_attribute' => 'sponsorship_type_id',
            'getter' => 'getSponsorshipType',
            'has' => 'hasSponsorshipType'
        ]
    ];

}