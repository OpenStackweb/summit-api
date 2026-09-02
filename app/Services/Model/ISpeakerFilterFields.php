<?php namespace services\model;
/**
 * Copyright 2026 OpenStack Foundation
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
 * Interface ISpeakerFilterFields
 *
 * The speaker filter field whitelist shared by every summit-scoped speaker filter/search
 * endpoint and the bulk speaker email send path. Referenced directly by name
 * (ISpeakerFilterFields::OPERATORS / ::VALIDATION_RULES) without implementing it, the same
 * way IEmailExcerptService's constants are used.
 *
 * @package services\model
 */
interface ISpeakerFilterFields
{
    const OPERATORS = [
        'id' => ['=='],
        'not_id' => ['=='],
        'first_name' => ['=@', '@@', '=='],
        'last_name' => ['=@', '@@', '=='],
        'email' => ['=@', '@@', '=='],
        'full_name' => ['=@', '@@', '=='],
        'member_id' => ['=='],
        'member_user_external_id' => ['=='],
        'has_accepted_presentations' => ['=='],
        'has_alternate_presentations' => ['=='],
        'has_rejected_presentations' => ['=='],
        'presentations_track_id' => ['=='],
        'presentations_track_group_id' => ['=='],
        'presentations_selection_plan_id' => ['=='],
        'presentations_type_id' => ['=='],
        'presentations_title' => ['=@', '@@', '=='],
        'presentations_abstract' => ['=@', '@@', '=='],
        'presentations_submitter_full_name' => ['=@', '@@', '=='],
        'presentations_submitter_email' => ['=@', '@@', '=='],
        'has_media_upload_with_type' => ['=='],
        'has_not_media_upload_with_type' => ['=='],
    ];

    const VALIDATION_RULES = [
        'id' => 'sometimes|integer',
        'not_id' => 'sometimes|integer',
        'first_name' => 'sometimes|string',
        'last_name' => 'sometimes|string',
        'email' => 'sometimes|string',
        'full_name' => 'sometimes|string',
        'member_id' => 'sometimes|integer',
        'member_user_external_id' => 'sometimes|integer',
        'has_accepted_presentations' => 'sometimes|string|in:true,false',
        'has_alternate_presentations' => 'sometimes|string|in:true,false',
        'has_rejected_presentations' => 'sometimes|string|in:true,false',
        'presentations_track_id' => 'sometimes|integer',
        'presentations_track_group_id' => 'sometimes|integer',
        'presentations_selection_plan_id' => 'sometimes|integer',
        'presentations_type_id' => 'sometimes|integer',
        'presentations_title' => 'sometimes|string',
        'presentations_abstract' => 'sometimes|string',
        'presentations_submitter_full_name' => 'sometimes|string',
        'presentations_submitter_email' => 'sometimes|string',
        'has_media_upload_with_type' => 'sometimes|integer',
        'has_not_media_upload_with_type' => 'sometimes|integer',
    ];
}
