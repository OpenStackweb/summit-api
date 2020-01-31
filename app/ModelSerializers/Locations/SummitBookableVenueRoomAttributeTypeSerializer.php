<?php namespace App\ModelSerializers\Locations;
/**
 * Copyright 2019 OpenStack Foundation
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

use Libs\ModelSerializers\AbstractSerializer;
use models\summit\SummitBookableVenueRoomAttributeType;
use ModelSerializers\SerializerRegistry;
use ModelSerializers\SilverStripeSerializer;
/**
 * Class SummitBookableVenueRoomAttributeTypeSerializer
 * @package App\ModelSerializers\Locations
 */
class SummitBookableVenueRoomAttributeTypeSerializer extends SilverStripeSerializer
{
    protected static $array_mappings = [
        'Type'     => 'type:json_string',
        'SummitId' => 'summit_id:json_int',
    ];

    public function serialize($expand = null, array $fields = array(), array $relations = array(), array $params = array() )
    {
        $attr_type   = $this->object;
        if(!$attr_type instanceof SummitBookableVenueRoomAttributeType)
            return [];

        $values = parent::serialize($expand, $fields, $relations, $params);
        $attr_values = [];
        foreach ($attr_type->getValues() as $attr_val){
            $attr_values[] = $attr_val->getId();
        }
        $values['values'] = $attr_values;
        if (!empty($expand)) {
            $exp_expand = explode(',', $expand);
            foreach ($exp_expand as $relation) {
                $relation = trim($relation);
                switch ($relation) {
                    case 'values': {
                        unset($values['values']);
                        $attr_values = [];
                        foreach ($attr_type->getValues() as $attr_val){
                            $attr_values[] = SerializerRegistry::getInstance()->getSerializer($attr_val)->serialize(AbstractSerializer::filterExpandByPrefix($expand, $relation));
                        }
                        $values['values'] = $attr_values;
                    }
                        break;

                }
            }
        }
        return $values;
    }
}