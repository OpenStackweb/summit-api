<?php namespace App\ModelSerializers\Software;
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

use App\Models\Foundation\Software\OpenStackRelease;
use Libs\ModelSerializers\Many2OneExpandSerializer;
use models\summit\SummitRegistrationInvitation;
use ModelSerializers\SilverStripeSerializer;
/**
 * Class OpenStackReleaseSerializer
 * @package App\ModelSerializers\Software
 */
final class OpenStackReleaseSerializer extends SilverStripeSerializer
{
    /**
     * @var array
     */
    protected static $array_mappings = [
        'Name'           => 'name:json_string',
        'ReleaseNumber'  => 'release_number:json_string',
        'ReleaseDate' => 'release_date:datetime_epoch',
        'Status' => 'status:json_string'
    ];

    protected static $allowed_relations = [
        'components',
    ];

    /**
     * @param null $expand
     * @param array $fields
     * @param array $relation
     * @param array $params
     * @return array
     */
    public function serialize($expand = null, array $fields = array(), array $relations = array(), array $params = array())
    {
        $release = $this->object;
        if (!$release instanceof OpenStackRelease) return [];

        if (!count($relations)) $relations = $this->getAllowedRelations();
        $values  = parent::serialize($expand, $fields, $relations, $params);

        if(in_array('components', $relations) && !isset($values['components'])){
            $components = [];
            foreach ($release->getOrderedComponents() as $component){
                $components[] = $component->getId();
            }
            $values['components'] = $components;
        }
        return $values;
    }

    protected static $expand_mappings = [
        'components' => [
            'type' => Many2OneExpandSerializer::class,
            'getter' => 'getOrderedComponents',
        ]
    ];
}