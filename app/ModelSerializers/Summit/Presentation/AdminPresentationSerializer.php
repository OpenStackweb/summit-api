<?php namespace ModelSerializers;
use models\summit\Presentation;
use models\summit\SummitEvent;

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
 * Class AdminPresentationSerializer
 * @package ModelSerializers
 */
class AdminPresentationSerializer extends PresentationSerializer
{
    protected static $array_mappings = [
        'Rank'              => 'rank:json_int',
        'SelectionStatus'   => 'selection_status:json_string',
    ];

    protected static $allowed_fields = [
        'rank',
        'selection_status',
    ];

    /**
     * @return string
     */
    protected function getSpeakersSerializerType():string{
        return SerializerRegistry::SerializerType_Private;
    }

    /**
     * @param null $expand
     * @param array $fields
     * @param array $relations
     * @param array $params
     * @return array
     */
    public function serialize(
        $expand = null, array $fields = array(), array $relations = array(), array $params = array())
    {
        $presentation = $this->object;
        if (!$presentation instanceof Presentation) return [];

        if (!count($relations)) $relations = $this->getAllowedRelations();

        $values = parent::serialize($expand, $fields, $relations, $params);

        // always set
        $values['streaming_url'] = $presentation->getStreamingUrl();
        $values['streaming_type'] = $presentation->getStreamingType();
        $values['etherpad_link'] = $presentation->getEtherpadLink();

        return $values;
    }
}