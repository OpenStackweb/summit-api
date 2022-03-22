<?php namespace ModelSerializers;
/**
 * Copyright 2017 OpenStack Foundation
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
use models\summit\PresentationType;
/**
 * Class PresentationEventTypeSerializer
 * @package ModelSerializers
 */
final class PresentationTypeSerializer extends SummitEventTypeSerializer
{
    protected static $array_mappings = [
        'MaxSpeakers'              => 'max_speakers:json_int',
        'MinSpeakers'              => 'min_speakers:json_int',
        'MaxModerators'            => 'max_moderators:json_int',
        'MinModerators'            => 'min_moderators:json_int',
        'UseSpeakers'              => 'use_speakers:json_boolean',
        'AreSpeakersMandatory'     => 'are_speakers_mandatory:json_boolean',
        'UseModerator'             => 'use_moderator:json_boolean',
        'ModeratorMandatory'       => 'is_moderator_mandatory:json_boolean',
        'ModeratorLabel'           => 'moderator_label:json_string',
        'ShouldBeAvailableOnCfp'   => 'should_be_available_on_cfp:json_boolean',
        'AllowCustomOrdering'      => 'allow_custom_ordering:json_boolean',
        'AllowAttendeeVote'        => 'allow_attendee_vote:json_boolean',
        'AllowsSpeakerEventCollision' => 'allows_speaker_event_collision:json_boolean',
    ];

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
        $type = $this->object;
        if (!$type instanceof PresentationType) return [];

        $allowed_media_upload_types = [];

        foreach ($type->getAllowedMediaUploadTypes() as $media_type){
            $allowed_media_upload_types[] = $media_type->getId();
        }

        $values['allowed_media_upload_types'] = $allowed_media_upload_types;

        if (!empty($expand)) {
            foreach (explode(',', $expand) as $relation) {
                $relation = trim($relation);
                switch ($relation) {

                    case 'allowed_media_upload_types': {
                        unset($values['allowed_media_upload_types']);
                        $allowed_media_upload_types = [];

                        foreach ($type->getAllowedMediaUploadTypes() as $media_type){
                            $allowed_media_upload_types[] = SerializerRegistry::getInstance()->getSerializer($media_type)->serialize(AbstractSerializer::filterExpandByPrefix($expand, $relation));
                        }

                        $values['allowed_media_upload_types'] = $allowed_media_upload_types;
                    }
                        break;
                }
            }
        }
        return $values;
    }
}