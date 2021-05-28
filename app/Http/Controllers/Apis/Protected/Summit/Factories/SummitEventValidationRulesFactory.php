<?php namespace App\Http\Controllers;
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
 * Class SummitEventValidationRulesFactory
 * @package App\Http\Controllers
 */
final class SummitEventValidationRulesFactory
{
    /**
     * @param array $data
     * @param bool $update
     * @return string[]
     */
    public static function build(array $data, bool $update = false):array{

        if($update){
            return [
                // summit event rules
                'title'                          => 'sometimes|string|max:255',
                'description'                    => 'sometimes|string|max:1100',
                'rsvp_link'                      => 'nullable|sometimes|url',
                'streaming_url'                  => 'nullable|sometimes|url',
                'etherpad_link'                  => 'nullable|sometimes|url',
                'meeting_url'                    => 'nullable|sometimes|url',
                'rsvp_template_id'               => 'sometimes|integer',
                'rsvp_max_user_number'           => 'required_with:rsvp_template_id|integer|min:0',
                'rsvp_max_user_wait_list_number' => 'required_with:rsvp_template_id|integer|min:0',
                'head_count'                     => 'sometimes|integer',
                'social_description'             => 'sometimes|string|max:110',
                'location_id'                    => 'sometimes|integer',
                'start_date'                     => 'sometimes|date_format:U',
                'end_date'                       => 'sometimes|required_with:start_date|date_format:U|after:start_date',
                'allow_feedback'                 => 'sometimes|boolean',
                'type_id'                        => 'sometimes|required|integer',
                'track_id'                       => 'sometimes|required|integer',
                'tags'                           => 'sometimes|string_array',
                'sponsors'                       => 'sometimes|int_array',
                'level'                          => 'sometimes|string',
                // presentation rules
                'attendees_expected_learnt'      => 'sometimes|string|max:1100',
                'attending_media'                => 'sometimes|boolean',
                'to_record'                      => 'sometimes|boolean',
                'speakers'                       => 'sometimes|int_array',
                'moderator_speaker_id'           => 'sometimes|integer',
                'extra_questions'                => 'sometimes|extra_question_dto_array',
                // group event
                'groups'                         => 'sometimes|int_array',
                'occupancy'                      => 'sometimes|in:EMPTY,25%,50%,75%,FULL',
                'selection_plan_id'              => 'sometimes|integer',
                'disclaimer_accepted'       => 'sometimes|boolean',
            ];
        }

        return [
            'title'                          => 'required|string|max:255',
            'description'                    => 'required|string|max:1100',
            'type_id'                        => 'required|integer',
            'location_id'                    => 'sometimes|integer',
            'start_date'                     => 'sometimes|required|date_format:U',
            'end_date'                       => 'sometimes|required_with:start_date|date_format:U|after:start_date',
            'track_id'                       => 'required|integer',
            'rsvp_link'                      => 'nullable|sometimes|url',
            'streaming_url'                  => 'nullable|sometimes|url',
            'etherpad_link'                  => 'nullable|sometimes|url',
            'meeting_url'                    => 'nullable|sometimes|url',
            'rsvp_template_id'               => 'sometimes|integer',
            'rsvp_max_user_number'           => 'required_with:rsvp_template_id|integer|min:0',
            'rsvp_max_user_wait_list_number' => 'required_with:rsvp_template_id|integer|min:0',
            'head_count'                     => 'sometimes|integer',
            'social_description'             => 'sometimes|string|max:110',
            'allow_feedback'                 => 'sometimes|boolean',
            'tags'                           => 'sometimes|string_array',
            'sponsors'                       => 'sometimes|int_array',
            'level'                          => 'sometimes|string',
            // presentation rules
            'attendees_expected_learnt'      =>  'sometimes|string|max:1100',
            'attending_media'                =>  'sometimes|boolean',
            'to_record'                      =>  'sometimes|boolean',
            'speakers'                       =>  'sometimes|int_array',
            'moderator_speaker_id'           =>  'sometimes|integer',
            'extra_questions'                => 'sometimes|extra_question_dto_array',
            // group event
            'groups'                         =>  'sometimes|int_array',
            'selection_plan_id'              =>  'sometimes|integer',
            'disclaimer_accepted'       => 'sometimes|boolean',
        ];
    }

    /**
     * @param array $data
     * @param bool $update
     * @return string[]
     */
    public static function buildForSubmission(array $data, bool $update = false){

        if($update){
            return  [
                'title'                     => 'required|string|max:255',
                'description'               => 'required|string|max:1100',
                'social_description'        => 'sometimes|string|max:110',
                'attendees_expected_learnt' => 'required|string|max:1100',
                'will_all_speakers_attend'  => 'sometimes|boolean',
                'type_id'                   => 'required|integer',
                'track_id'                  => 'required|integer',
                'attending_media'           => 'required|boolean',
                'links'                     => 'sometimes|url_array',
                'tags'                      => 'sometimes|string_array',
                'extra_questions'           => 'sometimes|extra_question_dto_array',
                'disclaimer_accepted'       => 'sometimes|boolean',
            ];
        }

        return [
            'title'                     => 'required|string|max:255',
            'description'               => 'required|string|max:1100',
            'social_description'        => 'sometimes|string|max:110',
            'attendees_expected_learnt' => 'required|string|max:1100',
            'type_id'                   => 'required|integer',
            'track_id'                  => 'required|integer',
            'attending_media'           => 'required|boolean',
            'will_all_speakers_attend'  => 'sometimes|boolean',
            'links'                     => 'sometimes|url_array',
            'tags'                      => 'sometimes|string_array',
            'extra_questions'           => 'sometimes|extra_question_dto_array',
            'disclaimer_accepted'       => 'sometimes|boolean',
        ];
    }
}