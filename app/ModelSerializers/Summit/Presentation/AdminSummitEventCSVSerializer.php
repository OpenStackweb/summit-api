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
    protected static $array_mappings = [
        'Occupancy' => 'occupancy:json_string',
        'OverflowStreamingUrl' => 'overflow_streaming_url:json_string',
        'OverflowStreamIsSecure' => 'overflow_stream_is_secure:json_boolean',
        'OverflowStreamKey' => 'overflow_stream_key:json_string',
    ];

    protected static $allowed_fields = [
        'occupancy',
        'created_by',
        'type',
        'track',
        'location_name',
        'overflow_streaming_url',
        'overflow_stream_is_secure',
        'overflow_stream_key'
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
        $values = parent::serialize($expand, $fields, $relations, $params);
        $summit_event = $this->object;
        if(!$summit_event instanceof SummitEvent) return $values;

        if(isset($values['description'])){
            $values['description'] = strip_tags($values['description']);
        }

        if (in_array('occupancy', $fields)) {
            $values['occupancy'] = $summit_event->getOccupancy();
        }

        if(in_array("type",$fields) && $summit_event->hasType()) {
            $values['type'] = $summit_event->getType()->getType();
        }

        if(in_array("track",$fields) && $summit_event->hasCategory()){
            $values['track'] = $summit_event->getCategory()->getTitle();
        }

        if(in_array("created_by",$fields)) {
            $values['created_by'] = '';
            if ($summit_event->hasCreatedBy()) {
                unset($values['created_by_id']);
                $created_by = $summit_event->getCreatedBy();
                $values['created_by'] = sprintf("%s (%s)", $created_by->getFullName(), $created_by->getEmail());
            }
        }

        if(in_array("location_name",$fields) && $summit_event->hasLocation()){
            $values['location_name'] = $summit_event->getLocation()->getName();
        }

        if (in_array('overflow_streaming_url', $fields)) {
            $values['overflow_streaming_url'] = $summit_event->getOverflowStreamingUrl();
        }

        if (in_array('overflow_stream_is_secure', $fields)) {
            $values['overflow_stream_is_secure'] = $summit_event->gatOverflowStreamIsSecure();
        }

        if (in_array('overflow_stream_key', $fields)) {
            $values['overflow_stream_key'] = $summit_event->getOverflowStreamKey();
        }

        return $values;
    }
}