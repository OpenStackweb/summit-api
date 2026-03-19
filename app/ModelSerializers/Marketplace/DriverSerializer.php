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

use App\Models\Foundation\Marketplace\Driver;
use Libs\ModelSerializers\Many2OneExpandSerializer;
use ModelSerializers\SilverStripeSerializer;

/**
 * Class DriverSerializer
 * @package App\ModelSerializers\Marketplace
 */
class DriverSerializer extends SilverStripeSerializer
{
    protected static $array_mappings = [
        'Name'        => 'name:json_string',
        'Description' => 'description:json_string',
        'Project'     => 'project:json_string',
        'Vendor'      => 'vendor:json_string',
        'Url'         => 'url:json_string',
        'Tested'      => 'tested:json_boolean',
        'Active'      => 'active:json_boolean',
    ];

    /**
     * @param $expand
     * @param array $fields
     * @param array $relations
     * @param array $params
     * @return array
     */
    public function serialize($expand = null, array $fields = [], array $relations = [], array $params = [])
    {
        $driver = $this->object;
        if (!$driver instanceof Driver) return [];
        $values = parent::serialize($expand, $fields, $relations, $params);

        if (in_array('releases', $relations) && !isset($values['releases'])) {
            $releases = [];
            foreach ($driver->getReleases() as $r) {
                $releases[] = $r->getId();
            }
            $values['releases'] = $releases;
        }
        return $values;
    }

    protected static $allowed_relations = [
        'releases',
    ];

    protected static $expand_mappings = [
        'releases' => [
            'type' => Many2OneExpandSerializer::class,
            'getter' => 'getReleases',
        ],
    ];
}
