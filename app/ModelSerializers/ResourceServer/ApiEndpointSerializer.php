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

use App\Models\ResourceServer\ApiEndpoint;
use Libs\ModelSerializers\AbstractSerializer;
use ModelSerializers\SerializerRegistry;

/**
 * Class ApiEndpointSerializer
 * @package App\ModelSerializers\ResourceServer
 */
final class ApiEndpointSerializer extends AbstractSerializer
{
    protected static $array_mappings = [
        'Id'            => 'id:json_int',
        'Name'          => 'name:json_string',
        'Description'   => 'description:json_string',
        'Active'        => 'active:json_boolean',
        'HttpMethod'    => 'http_method:json_string',
        'Route'         => 'route:json_string',
    ];

    protected static $allowed_relations = [
        'scopes',
        'authz_groups',
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
        $endpoint  = $this->object;
        if(!$endpoint instanceof ApiEndpoint) return [];

        if(!count($relations)) $relations = $this->getAllowedRelations();

        $values = parent::serialize($expand, $fields, $relations, $params);

        if(in_array('scopes', $relations))
            $values['scopes'] = $endpoint->getScopeIds();

        if(in_array('authz_groups', $relations))
            $values['authz_groups'] = $endpoint->getAuthGroupIds();

        if (!empty($expand)) {
            foreach (explode(',', $expand) as $relation) {
                $relation = trim($relation);
                switch (trim($relation)) {

                    case 'scopes': {
                        if(!in_array('scopes', $relations)) break;
                        $scopes = [];
                        unset($values['scopes']);
                        foreach ($endpoint->getScopes() as $e) {
                            $scopes[] = SerializerRegistry::getInstance()->getSerializer($e)->serialize(AbstractSerializer::filterExpandByPrefix($expand, $relation));
                        }
                        $values['scopes'] = $scopes;
                    }
                        break;
                    case 'authz_groups': {
                        if(!in_array('authz_groups', $relations)) break;
                        $authz_groups = [];
                        unset($values['authz_groups']);
                        foreach ($endpoint->getAuthzGroups() as $e) {
                            $authz_groups[] = SerializerRegistry::getInstance()->getSerializer($e)->serialize(AbstractSerializer::filterExpandByPrefix($expand, $relation));
                        }
                        $values['authz_groups'] = $authz_groups;
                    }
                        break;

                }
            }
        }
        return $values;
    }
}