<?php namespace ModelSerializers;
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
use models\summit\SummitBadgeType;
/**
 * Class SummitBadgeTypeSerializer
 * @package ModelSerializers
 */
final class SummitBadgeTypeSerializer extends SilverStripeSerializer
{
    protected static $array_mappings = [
        'Name'              => 'name:json_string',
        'Description'       => 'description:json_string',
        'TemplateContent'   => 'template_content:json_string',
        'Default'           => 'is_default:json_boolean',
        'SummitId'          => 'summit_id:json_int',
    ];

    protected static $allowed_relations = [
        'access_levels',
        'badge_features',
    ];

    /**
     * @param null $expand
     * @param array $fields
     * @param array $relations
     * @param array $params
     * @return array
     */
    public function serialize($expand = null, array $fields = array(), array $relations = array(), array $params = array() )
    {
        $badge_type = $this->object;
        if (!$badge_type instanceof SummitBadgeType) return [];
        $values = parent::serialize($expand, $fields, $relations, $params);

        if(!count($relations)) $relations = $this->getAllowedRelations();
        // access_levels
        if(in_array('access_levels', $relations)) {
            $access_levels = [];
            foreach ($badge_type->getAccessLevels() as $access_level) {
                $access_levels[] = $access_level->getId();
            }
            $values['access_levels'] = $access_levels;
        }

        // badge_features
        if(in_array('badge_features', $relations)) {
            $features = [];
            foreach ($badge_type->getBadgeFeatures() as $feature) {
                $features[] = $feature->getId();
            }
            $values['badge_features'] = $features;
        }

        if (!empty($expand)) {
            $exp_expand = explode(',', $expand);
            foreach ($exp_expand as $relation) {
                switch (trim($relation)) {
                    case 'access_levels': {
                        unset($values['access_levels']);
                        $access_levels = [];
                        foreach ($badge_type->getAccessLevels() as $access_level) {
                            $access_levels[] = SerializerRegistry::getInstance()->getSerializer($access_level)->serialize(AbstractSerializer::getExpandForPrefix('access_levels', $expand));
                        }
                        $values['access_levels'] = $access_levels;
                    }
                        break;
                    case 'badge_features': {
                        unset($values['badge_features']);
                        $badge_features = [];
                        foreach ($badge_type->getBadgeFeatures() as $feature) {
                            $badge_features[] = SerializerRegistry::getInstance()->getSerializer($feature)->serialize(AbstractSerializer::getExpandForPrefix('badge_features', $expand));
                        }
                        $values['badge_features'] = $badge_features;
                    }
                    break;
                }

            }
        }
        return $values;
    }
}