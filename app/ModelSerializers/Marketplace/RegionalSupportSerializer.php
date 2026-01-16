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
use App\Models\Foundation\Marketplace\RegionalSupport;
use Libs\ModelSerializers\Many2OneExpandSerializer;
use Libs\ModelSerializers\One2ManyExpandSerializer;
use ModelSerializers\SerializerRegistry;
use ModelSerializers\SilverStripeSerializer;

/**
 * Class RegionalSupportSerializer
 * @package App\ModelSerializers\Marketplace
 */
class RegionalSupportSerializer extends SilverStripeSerializer
{
     protected static $array_mappings = [
        'RegionId'       => 'region_id:json_int',
    ];

    protected static $allowed_relations = [
        'region',
        'supported_channel_types',
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
        $regional_support  = $this->object;
        if(!$regional_support instanceof RegionalSupport) return [];
        $values           = parent::serialize($expand, $fields, $relations, $params);

        if(in_array('supported_channel_types', $relations) && !isset($values['supported_channel_types'])) {
            $supported_channel_types = [];
            foreach ($regional_support->getSupportedChannelTypes() as $c) {
                $supported_channel_types[] = $c->getId();
            }
            $values['supported_channel_types'] = $supported_channel_types;
        }

        return $values;
    }

    protected static $expand_mappings = [
        'supported_channel_types' => [
            'type' => Many2OneExpandSerializer::class,
            'getter' => 'getSupportedChannelTypes',
        ],
        'region' => [
            'type' => One2ManyExpandSerializer::class,
            'original_attribute' => 'region_id',
            'getter' => 'getRegion',
            'has' => 'hasRegion'
        ],
    ];
}