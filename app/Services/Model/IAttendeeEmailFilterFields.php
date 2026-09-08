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
 * Interface IAttendeeEmailFilterFields
 *
 * The FilterParser operator whitelist shared by every consumer that resolves attendee ids for
 * the bulk email send path: OAuth2SummitAttendeesApiController::send (parsing the raw filter),
 * AttendeeService::triggerSend (re-parsing it to page ids before chunking), and
 * ProcessAttendeesEmailRequestJob::handle (parsing it again on a direct id-list retry payload).
 * Referenced directly by name (IAttendeeEmailFilterFields::OPERATORS) without implementing it,
 * the same way ISpeakerFilterFields::OPERATORS is used.
 *
 * Deliberately does not also carry a VALIDATION_RULES constant the way ISpeakerFilterFields
 * does: this codebase's boolean filter fields (has_company, has_notes, has_manager) validate
 * via `new \App\Rules\Boolean()` rule instances, and PHP does not allow `new` expressions inside
 * a class constant's value (confirmed empirically: "New expressions are not supported in this
 * context"). That validation array has exactly one consumer - the controller's own
 * $filter->validate() call - so there is no duplication to remove by relocating it; it stays
 * inline in OAuth2SummitAttendeesApiController::send.
 *
 * Scoped to the email-send endpoint only - the general attendee listing endpoint
 * (DoctrineSummitAttendeeRepository::getFilterMappings) supports a richer field set this
 * interface does not attempt to unify with.
 *
 * @package services\model
 */
interface IAttendeeEmailFilterFields
{
    const OPERATORS = [
        'id' => ['=='],
        'not_id' => ['=='],
        'first_name' => ['=@', '=='],
        'last_name' => ['=@', '=='],
        'full_name' => ['=@', '=='],
        'company' => ['=@', '=='],
        'has_company' => ['=='],
        'email' => ['=@', '=='],
        'external_order_id' => ['=@', '=='],
        'external_attendee_id' => ['=@', '=='],
        'member_id' => ['==', '>'],
        'ticket_type' => ['=@', '==', '@@'],
        'ticket_type_id' => ['=='],
        'badge_type' => ['=@', '==', '@@'],
        'badge_type_id' => ['=='],
        'features' => ['=@', '==', '@@'],
        'features_id' => ['=='],
        'access_levels' => ['=@', '==', '@@'],
        'access_levels_id' => ['=='],
        'status' => ['=@', '=='],
        'has_member' => ['=='],
        'has_tickets' => ['=='],
        'has_virtual_checkin' => ['=='],
        'has_checkin' => ['=='],
        'tickets_count' => ['==', '>=', '<=', '>', '<'],
        'presentation_votes_date' => ['==', '>=', '<=', '>', '<'],
        'presentation_votes_count' => ['==', '>=', '<=', '>', '<'],
        'presentation_votes_track_group_id' => ['=='],
        'summit_hall_checked_in_date' => ['==', '>=', '<=', '>', '<', '[]'],
        'tags' => ['=@', '==', '@@'],
        'tags_id' => ['=='],
        'notes' => ['=@', '@@'],
        'has_notes' => ['=='],
        'has_manager' => ['=='],
    ];
}
