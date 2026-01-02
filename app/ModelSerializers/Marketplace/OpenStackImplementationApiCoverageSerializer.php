<?php namespace App\ModelSerializers\Marketplace;
/**
 * Copyright 2017 OpenStack Foundation
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
use App\Models\Foundation\Marketplace\OpenStackImplementationApiCoverage;
use Libs\ModelSerializers\One2ManyExpandSerializer;
use ModelSerializers\SilverStripeSerializer;
/**
 * Class OpenStackImplementationApiCoverageSerializer
 * @package App\ModelSerializers\Marketplace
 */
class OpenStackImplementationApiCoverageSerializer extends SilverStripeSerializer
{
    /**
     * @var array
     */
    protected static $array_mappings = [
        'ClassName' => 'class_name:json_string',
        'Percent' => 'api_coverage:json_int',
        'ReleaseSupportedApiVersionId' => 'release_supported_api_version_id:json_int',
        'ImplementationId' => 'implementation_id:json_int',
    ];

    /**
     * @param null $expand
     * @param array $fields
     * @param array $relations
     * @param array $params
     * @return array
     */
    public function serialize($expand = null, array $fields = [], array $relations = [], array $params = [])
    {
        $api_coverage  = $this->object;
        if(!$api_coverage instanceof OpenStackImplementationApiCoverage) return [];
        $values = parent::serialize($expand, $fields, $relations, $params);
        return $values;
    }

    protected static $expand_mappings = [
        'release_supported_api_version' => [
            'type' => One2ManyExpandSerializer::class,
            'original_attribute' => 'release_supported_api_version_id',
            'getter' => 'getReleaseSupportedApiVersion',
            'has' => 'hasReleaseSupportedApiVersion'
        ],
        'implementation' => [
            'type' => One2ManyExpandSerializer::class,
            'original_attribute' => 'implementation_id',
            'getter' => 'getImplementation',
            'has' => 'hasImplementation'
        ]
    ];
}
