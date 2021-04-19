<?php namespace ModelSerializers;
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
use Libs\ModelSerializers\AbstractSerializer;
use models\summit\SummitDocument;
/**
 * Class SummitDocumentSerializer
 * @package ModelSerializers
 */
class SummitDocumentSerializer extends SilverStripeSerializer
{

    protected static $array_mappings = [
        'Name' => 'name:json_string',
        'Description' => 'description:json_string',
        'ShowAlways' => 'show_always:json_boolean',
        'Label' => 'label:json_string',
        'SummitId' => 'summit_id:json_int',
        'FileUrl' => 'file:json_url',
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
        $summit_document = $this->object;
        if (!$summit_document instanceof SummitDocument) return [];
        $values = parent::serialize($expand, $fields, $relations, $params);

        $event_types  = [];
        foreach ($summit_document->getEventTypes() as $event_type) {
            $event_types[] = $event_type->getId();
        }

        $values['event_types'] = $event_types;

        if (!empty($expand)) {
            $relations = explode(',', $expand);
            foreach ($relations as $relation) {
                $relation = trim($relation);
                switch ($relation) {
                    case 'event_types':{
                        $event_types  = [];
                        foreach ($summit_document->getEventTypes() as $event_type) {
                            $event_types[] = SerializerRegistry::getInstance()->getSerializer($event_type)->serialize(AbstractSerializer::filterExpandByPrefix($expand, $relation));
                        }
                        $values['event_types'] = $event_types;
                    }
                        break;
                    case 'summit':{
                        unset($values['summit_id']);
                        $values['summit'] = SerializerRegistry::getInstance()->getSerializer($summit_document->getSummit())->serialize(AbstractSerializer::filterExpandByPrefix($expand, $relation));
                    }
                        break;
                }
            }
        }

        return $values;
    }
}