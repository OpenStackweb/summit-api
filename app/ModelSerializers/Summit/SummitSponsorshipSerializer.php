<?php namespace App\ModelSerializers\Summit;

use Libs\ModelSerializers\AbstractSerializer;
use Libs\ModelSerializers\Many2OneExpandSerializer;
use Libs\ModelSerializers\One2ManyExpandSerializer;
use models\summit\SummitSponsorship;
use ModelSerializers\SerializerRegistry;
use ModelSerializers\SilverStripeSerializer;

/**
 * Copyright 2025 OpenStack Foundation
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

/**
 * Class SummitSponsorshipSerializer
 * @package ModelSerializers
 */
final class SummitSponsorshipSerializer extends SilverStripeSerializer
{
    protected static $array_mappings = [
        'TypeId' => 'type_id:json_int'
    ];

     protected static $allowed_relations = [
        'add_ons',
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
        $sponsorship = $this->object;
        if (!$sponsorship instanceof SummitSponsorship) return [];
        $values = parent::serialize($expand, $fields, $relations, $params);

        if (in_array('add_ons', $relations) && !isset($values['add_ons'])) {
            $add_ons = [];
            foreach ($sponsorship->getAddOns() as $add_on) {
                $add_ons[] = $add_on->getId();
            }
            $values['add_ons'] = $add_ons;
        }
        return $values;
    }

    protected static $expand_mappings = [
        'add_ons' => [
            'type' => Many2OneExpandSerializer::class,
            'getter' => 'getAddOns',
        ],
        'type' => [
            'type' => One2ManyExpandSerializer::class,
            'original_attribute' => 'type_id',
            'getter' => 'getType',
            'has' => 'hasType'
        ],
    ];
}