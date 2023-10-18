<?php namespace ModelSerializers;
/*
 * Copyright 2023 OpenStack Foundation
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
 * Class AdminSummitEventWithFileCSVSerializer
 * @package ModelSerializers
 */
final class AdminSummitEventWithFileCSVSerializer extends AdminSummitEventWithFileSerializer
{
    protected static $allowed_fields = [
        'location_name',
    ];

    public function serialize($expand = null, array $fields = [], array $relations = [], array $params = [] )
    {
        if(!count($fields)) $fields = $this->getAllowedFields();

        $values = parent::serialize($expand, $fields, $relations, $params);
        $summit_event = $this->object;
        if(!$summit_event instanceof SummitEvent) return $values;

        if(in_array("location_name",$fields) && $summit_event->hasLocation()){
            $values['location_name'] = $summit_event->getLocation()->getName();
        }

        return $values;
    }
}