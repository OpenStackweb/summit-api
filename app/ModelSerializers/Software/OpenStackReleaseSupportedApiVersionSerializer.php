<?php namespace App\ModelSerializers\Software;
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
use ModelSerializers\SilverStripeSerializer;

class OpenStackReleaseSupportedApiVersionSerializer extends SilverStripeSerializer
{
    protected static $array_mappings = [
        'Version'      => 'version:json_string',
        'Status'       => 'status:json_string',
        'ComponentId' => 'component_id:json_int',
        'ApiVersionId' => 'api_version_id:json_int',
        'ReleaseId' => 'release_id:json_int',
    ];

    protected static $expand_mappings = [
        'component' => [
            'type' => One2ManyExpandSerializer::class,
            'original_attribute' => 'component_id',
            'getter' => 'getComponent',
            'has' => 'hasComponent'
        ],
        'api_version' => [
            'type' => One2ManyExpandSerializer::class,
            'original_attribute' => 'api_version_id',
            'getter' => 'getApiVersion',
            'has' => 'hasApiVersion'
        ],
        'release' => [
            'type' => One2ManyExpandSerializer::class,
            'original_attribute' => 'release_id',
            'getter' => 'getRelease',
            'has' => 'hasRelease'
        ],
    ];
}