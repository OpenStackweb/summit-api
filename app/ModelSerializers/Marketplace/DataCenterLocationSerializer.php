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

use App\Models\Foundation\Marketplace\DataCenterLocation;
use Libs\ModelSerializers\Many2OneExpandSerializer;
use Libs\ModelSerializers\One2ManyExpandSerializer;
use ModelSerializers\SilverStripeSerializer;

/**
 * Class DataCenterLocationSerializer
 * @package App\ModelSerializers\Marketplace
 */
final class DataCenterLocationSerializer extends SilverStripeSerializer
{
    /**
     * @var array
     */
    protected static $array_mappings = [
        'City' => 'city:json_string',
        'State' => 'state:json_string',
        'Country' => 'country:json_string',
        'Lat' => 'lat:json_float',
        'Lng' => 'lng:json_float',
        'RegionId' => 'region_id:json_int',
    ];

    protected static $allowed_relations = [
        'region',
        'availability_zones',
    ];

    protected static $expand_mappings = [
        'region' => [
            'type' => One2ManyExpandSerializer::class,
            'original_attribute' => 'region_id',
            'getter' => 'getRegion',
            'has' => 'hasRegion'
        ],
        'availability_zones' =>
            [
                'type' => Many2OneExpandSerializer::class,
                'getter' => 'getAvailabilityZones'
            ],
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

        $location = $this->object;
        if (!$location instanceof DataCenterLocation) return [];
        $values = parent::serialize($expand, $fields, $relations, $params);
        if (in_array('availability_zones', $relations) && !isset($values['availability_zones'])) {
            $availability_zones = [];
            foreach ($location->getAvailabilityZones() as $zone) {
                $availability_zones[] = $zone->getId();
            }
            $values['availability_zones'] = $availability_zones;
        }
        return $values;
    }
}