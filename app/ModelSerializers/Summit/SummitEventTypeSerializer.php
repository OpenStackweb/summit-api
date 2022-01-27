<?php namespace ModelSerializers;
use Libs\ModelSerializers\AbstractSerializer;
use models\summit\SummitDocument;
use models\summit\SummitEventType;

/**
 * Copyright 2016 OpenStack Foundation
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
 * Class SummitEventTypeSerializer
 * @package ModelSerializers
 */
class SummitEventTypeSerializer extends SilverStripeSerializer
{
    protected static $array_mappings = [
        'Type'                  => 'name:json_string',
        'ClassName'             => 'class_name:json_string',
        'Color'                 => 'color:json_color',
        'BlackoutTimes'         => 'black_out_times:json_boolean',
        'UseSponsors'           => 'use_sponsors:json_boolean',
        'AreSponsorsMandatory'  => 'are_sponsors_mandatory:json_boolean',
        'AllowsAttachment'      => 'allows_attachment:json_boolean',
        'AllowsLevel'           => 'allows_level:json_boolean',
        'AllowsPublishingDates' => 'allows_publishing_dates:json_boolean',
        'AllowsLocation'        => 'allows_location:json_boolean',
        'Default'               => 'is_default:json_boolean',
        'SummitId'              => 'summit_id:json_int',
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
        $event_type = $this->object;
        if (!$event_type instanceof SummitEventType) return [];
        $values = parent::serialize($expand, $fields, $relations, $params);

        $summit_documents  = [];
        foreach ($event_type->getSummitDocuments() as $document) {
            $summit_documents[] = $document->getId();
        }

        $values['summit_documents'] = $summit_documents;

        if (!empty($expand)) {
            $relations = explode(',', $expand);
            foreach ($relations as $relation) {
                $relation = trim($relation);
                switch ($relation) {
                    case 'summit_documents':{
                        $summit_documents  = [];
                        foreach ($event_type->getSummitDocuments() as $document) {
                            $summit_documents[] = SerializerRegistry::getInstance()->getSerializer($document)->serialize(AbstractSerializer::filterExpandByPrefix($expand, $relation));
                        }
                        $values['summit_documents'] = $summit_documents;
                    }
                        break;
                    case 'summit':{
                        unset($values['summit_id']);
                        $values['summit'] = SerializerRegistry::getInstance()->getSerializer($event_type->getSummit())->serialize(AbstractSerializer::filterExpandByPrefix($expand, $relation));
                    }
                        break;
                }
            }
        }

        return $values;
    }
}