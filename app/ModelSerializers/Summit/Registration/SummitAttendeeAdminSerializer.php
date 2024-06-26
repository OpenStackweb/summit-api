<?php namespace ModelSerializers;
use Libs\ModelSerializers\Many2OneExpandSerializer;

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
 * Class SummitAttendeeAdminSerializer
 * @package ModelSerializers
 */
class SummitAttendeeAdminSerializer extends SummitAttendeeSerializer
{
    protected static $array_mappings = [
        'VirtualCheckedIn' => 'has_virtual_check_in:json_boolean',
    ];

    protected static $allowed_relations = [
        'notes',
    ];

    protected static $expand_mappings = [
        'notes' => [
            'type' => Many2OneExpandSerializer::class,
            'getter' => 'getNotes',
            'should_verify_relation' => true
        ],
    ];
}