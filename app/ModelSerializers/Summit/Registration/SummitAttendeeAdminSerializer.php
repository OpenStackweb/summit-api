<?php namespace ModelSerializers;
use Libs\ModelSerializers\Many2OneExpandSerializer;
use models\summit\SummitAttendee;

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
        'member' => [
            'type' => SummitAttendeeMemberExpandSerializer::class,
            'original_attribute' => 'member_id',
            'getter' => 'getMember',
            'has' => 'hasMember',
            'serializer_type' => SerializerRegistry::SerializerType_Admin,
        ],
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

        $attendee = $this->object;
        if (!$attendee instanceof SummitAttendee) return $values;

        // Same fallback pattern as OpenStackReleaseSerializer::serialize() for `components`:
        // if `notes` is requested but not expanded, send ids; !isset(...) guards against
        // clobbering the full-object list the parent's own _expand() pass may have set.
        if (in_array('notes', $relations) && !isset($values['notes'])) {
            $notes = [];
            foreach ($attendee->getNotes() as $note) {
                $notes[] = $note->getId();
            }
            $values['notes'] = $notes;
        }

        return $values;
    }
}