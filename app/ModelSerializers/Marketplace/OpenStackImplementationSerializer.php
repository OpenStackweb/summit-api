<?php
namespace App\ModelSerializers\Marketplace;
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

use App\Models\Foundation\Marketplace\OpenStackImplementation;
use Libs\ModelSerializers\Many2OneExpandSerializer;
use ModelSerializers\SerializerRegistry;

/**
 * Class OpenStackImplementationSerializer
 * @package App\ModelSerializers\Marketplace
 */
class OpenStackImplementationSerializer extends RegionalSupportedCompanyServiceSerializer
{
    /**
     * @var array
     */
    protected static $array_mappings = [
        'CompatibleWithStorage'           => 'is_compatible_with_storage:json_boolean',
        'CompatibleWithCompute'           => 'is_compatible_with_compute:json_boolean',
        'CompatibleWithFederatedIdentity' => 'is_compatible_with_federated_identity:json_boolean',
        'CompatibleWithPlatform'          => 'is_compatible_with_platform:json_boolean',
        'OpenStackPowered'                => 'is_openstack_powered:json_boolean',
        'OpenStackTested'                 => 'is_openstack_tested:json_boolean',
        'OpenStackTestedLabel'            => 'openstack_tested_info:json_string',
        "UsesIronic"                      => 'uses_ironic:json_boolean',
    ];

    protected static $allowed_relations = [
        'capabilities',
        'guests',
        'hypervisors',
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

        $implementation  = $this->object;
        if(!$implementation instanceof OpenStackImplementation) return [];
        $values           = parent::serialize($expand, $fields, $relations, $params);

        if(in_array('capabilities', $relations) && !isset($values['capabilities'])) {
            $capabilities = [];
            foreach ($implementation->getCapabilities() as $c) {
                $capabilities[] = $c->getId();
            }
            $values['capabilities'] = $capabilities;
        }
        if(in_array('hypervisors', $relations) && !isset($values['hypervisors'])) {
            $hypervisors = [];
            foreach ($implementation->getHypervisors() as $h) {
                $hypervisors[] = $h->getId();
            }
            $values['hypervisors'] = $hypervisors;
        }
        if(in_array('guests', $relations) && !isset($values['guests'])) {
            $guests = [];
            foreach ($implementation->getGuests() as $g) {
                $guests[] = $g->getId();
            }
            $values['guests'] = $guests;
        }
        return $values;
    }

    protected static $expand_mappings = [
        'capabilities' => [
            'type' => Many2OneExpandSerializer::class,
            'getter' => 'getCapabilities',
        ],
        'hypervisors' => [
            'type' => Many2OneExpandSerializer::class,
            'getter' => 'getHypervisors',
        ],
        'guests' => [
            'type' => Many2OneExpandSerializer::class,
            'getter' => 'getGuests',
        ],
    ];
}