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

/**
 * Class AdminSummitEventSerializer
 * @package ModelSerializers
 */
class AdminSummitEventSerializer extends SummitEventSerializer
{
    protected static $array_mappings = [
        'Occupancy' => 'occupancy:json_string',
        'StreamingUrl' => 'streaming_url:json_url',
        'StreamingType' => 'streaming_type:json_string',
        'EtherpadLink' => 'etherpad_link:json_url',
        'OverflowStreamingUrl' => 'overflow_streaming_url:json_url',
        'OverflowStreamIsSecure' => 'overflow_stream_is_secure:json_boolean',
        'OverflowStreamKey' => 'overflow_stream_key:json_string',
    ];

    protected static $allowed_fields = [
        'occupancy',
        'streaming_url',
        'streaming_type',
        'etherpad_link',
        'overflow_streaming_url',
        'overflow_stream_is_secure',
        'overflow_stream_key'
    ];

    /**
     * @param string|null $relation
     * @return string
     */
    protected function getSerializerType(?string $relation=null):string{
        $relation = trim($relation);
        if($relation == 'created_by')
            return SerializerRegistry::SerializerType_Admin;
        if($relation == 'updated_by')
            return SerializerRegistry::SerializerType_Admin;

        return SerializerRegistry::SerializerType_Private;
    }
}