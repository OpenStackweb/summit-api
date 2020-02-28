<?php namespace App\ModelSerializers\ResourceServer;
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

use App\Models\ResourceServer\Api;
use Libs\ModelSerializers\AbstractSerializer;
use ModelSerializers\SerializerRegistry;

/**
 * Class ApiSerializer
 * @package App\ModelSerializers\ResourceServer
 */
final class ApiSerializer extends AbstractSerializer
{
    protected static $array_mappings = [
        'Id'            => 'id:json_int',
        'Name'          => 'name:json_string',
        'Description'   => 'description:json_string',
        'Active'        => 'active:json_boolean',
    ];

    protected static $allowed_relations = [
        'scopes',
        'endpoints',
    ];

    /**
     * @param null $expand
     * @param array $fields
     * @param array $relations
     * @param array $params
     * @return array
     */
    public function serialize($expand = null, array $fields = array(), array $relations = array(), array $params = array())
    {
        $api  = $this->object;
        if(!$api instanceof Api) return [];

        if(!count($relations)) $relations = $this->getAllowedRelations();

        $values = parent::serialize($expand, $fields, $relations, $params);

        if(in_array('scopes', $relations))
            $values['scopes'] = $api->getScopeIds();

        if(in_array('endpoints', $relations))
            $values['endpoints'] = $api->getEndpointsIds();

        if (!empty($expand)) {
            foreach (explode(',', $expand) as $relation) {
                $relation = trim($relation);
                switch (trim($relation)) {
                    case 'scopes': {
                        if(!in_array('scopes', $relations)) break;
                        $scopes = [];
                        unset($values['scopes']);
                        foreach ($api->getScopes() as $s) {
                            $scopes[] = SerializerRegistry::getInstance()->getSerializer($s)->serialize(AbstractSerializer::filterExpandByPrefix($expand, $relation));
                        }
                        $values['scopes'] = $scopes;
                    }
                    break;
                    case 'endpoints': {
                        if(!in_array('endpoints', $relations)) break;
                        $endpoints = [];
                        unset($values['endpoints']);
                        foreach ($api->getEndpoints() as $e) {
                            $endpoints[] = SerializerRegistry::getInstance()->getSerializer($e)->serialize(AbstractSerializer::filterExpandByPrefix($expand, $relation));
                        }
                        $values['endpoints'] = $endpoints;
                    }
                    break;

                }
            }
        }
        return $values;
    }
}

