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
 * Class AdminSummitEventCSVSerializer
 * @package ModelSerializers
 */
class AdminSummitEventCSVSerializer extends SummitEventSerializer
{
    /**
     * @param null $expand
     * @param array $fields
     * @param array $relations
     * @param array $params
     * @return array
     */
    public function serialize($expand = null, array $fields = [], array $relations = [], array $params = [] )
    {
        $values = parent::serialize($expand, $fields, $relations, $params);
        $summit_event = $this->object;
        if(!$summit_event instanceof SummitEvent) return $values;
        if(isset($values['description'])){
            $values['description'] = strip_tags($values['description']);
        }
        if($summit_event->hasType()) {
            $values['type'] = $summit_event->getType()->getType();
        }
        if($summit_event->hasCategory()){
            $values['track'] = $summit_event->getCategory()->getTitle();
        }
        return $values;
    }
}