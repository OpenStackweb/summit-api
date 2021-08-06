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
use models\summit\SummitEvent;
/**
 * Class AdminSummitEventSerializer
 * @package ModelSerializers
 */
class AdminSummitEventSerializer extends SummitEventSerializer
{
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
        $event = $this->object;
        if (!$event instanceof SummitEvent) return [];

        if (!count($relations)) $relations = $this->getAllowedRelations();

        $values = parent::serialize($expand, $fields, $relations, $params);

        // always set
        $values['streaming_url'] = $event->getStreamingUrl();
        $values['etherpad_link'] = $event->getEtherpadLink();

        return $values;
    }
}