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
use App\ModelSerializers\Traits\RequestScopedCache;
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
        'allowed_view_types',
    ];

    use RequestScopedCache;

    /**
     * @param null $expand
     * @param array $fields
     * @param array $relations
     * @param array $params
     * @return array
     */
    public function serialize($expand = null, array $fields = array(), array $relations = array(), array $params = array() )
    {
        return $this->cache($this->getRequestKey
        (
            "SummitBadgeTypeSerializer",
            $this->object->getIdentifier(),
            $expand,
            $fields,
            $relations
        ), function () use ($expand, $fields, $relations, $params) {
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

            // allowed_view_types
            if(in_array('allowed_view_types', $relations)) {
                $allowed_view_types = [];
                foreach ($badge_type->getAllowedViewTypes() as $viewType) {
                    $features[] = $viewType->getId();
                }
                $values['allowed_view_types'] = $allowed_view_types;
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
                        case 'allowed_view_types': {
                            unset($values['allowed_view_types']);
                            $allowed_view_types = [];
                            foreach ($badge_type->getAllowedViewTypes() as $viewType) {
                                $allowed_view_types[] = SerializerRegistry::getInstance()->getSerializer($viewType)->serialize(AbstractSerializer::getExpandForPrefix('allowed_view_types', $expand));
                            }
                            $values['allowed_view_types'] = $allowed_view_types;
                        }
                            break;
                    }

                }
            }
            return $values;
        });
    }
}