<?php namespace ModelSerializers;
/**
 * Copyright 2021 OpenStack Foundation
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
use models\summit\SummitSelectedPresentation;
/**
 * Class SummitSelectedPresentationSerializer
 * @package ModelSerializers
 */
class SummitSelectedPresentationSerializer extends SilverStripeSerializer
{
    protected static $array_mappings = [
        'Collection'     => 'type:json_string',
        'Order'          => 'order:json_int',
        'ListId'         => 'list_id:json_int',
        'PresentationId' => 'presentation_id:json_int',
    ];

    protected static $allowed_fields = [
        'type',
        'category_id',
        'presentation_id',
        'order',
        'list_id',
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
        if (!count($relations)) $relations = $this->getAllowedRelations();

        $selected_presentation = $this->object;

        if (!$selected_presentation instanceof SummitSelectedPresentation) return [];

        $values = parent::serialize($expand, $fields, $relations, $params);

        if (!empty($expand)) {
            foreach (explode(',', $expand) as $relation) {
                $relation = trim($relation);
                switch ($relation) {
                    case 'presentation':
                    {
                        if ($selected_presentation->getPresentationId() > 0) {
                            unset($values['presentation_id']);
                            $values['presentation'] = SerializerRegistry::getInstance()->getSerializer
                            (
                                $selected_presentation->getPresentation(),
                                IPresentationSerializerTypes::TrackChairs
                            )->serialize(AbstractSerializer::filterExpandByPrefix($expand, $relation));
                        }
                    }
                    break;
                    case 'list':
                    {
                        if ($selected_presentation->getListId() > 0) {
                            unset($values['list_id']);
                            $values['list'] = SerializerRegistry::getInstance()->getSerializer($selected_presentation->getList())->serialize(AbstractSerializer::filterExpandByPrefix($expand, $relation));
                        }
                    }
                    break;
                }
            }
        }
        return $values;
    }
}