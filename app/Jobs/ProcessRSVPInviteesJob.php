<?php namespace App\Jobs;
/**
 * Copyright 2025 OpenStack Foundation
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
use App\Services\ISummitRSVPInvitationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use models\summit\SummitEvent;
use utils\FilterParser;

class ProcessRSVPInviteesJob implements ShouldQueue
{
    public $tries = 1;

    // no timeout
    public $timeout = 0;

    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $payload;

    private $filter;

    private $summit_event_id;

    /**
     * ProcessRSVPInviteesJob constructor.
     * @param SummitEvent $summit_event
     * @param array $payload
     * @param $filter
     */
    public function __construct(SummitEvent $summit_event, array $payload, $filter)
    {
        $this->summit_event_id = $summit_event->getId();
        $this->payload = $payload;
        $this->filter = $filter;
    }

    /**
     * @param ISummitRSVPInvitationService $service
     * @throws \utils\FilterParserException
     */
    public function handle(ISummitRSVPInvitationService $service){

        Log::debug(sprintf("ProcessRSVPInviteesJob::handle summit event id %s", $this->summit_event_id));

        $filter = !is_null($this->filter) ? FilterParser::parse($this->filter, [
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
            'summit_hall_checked_in_date' => ['==', '>=', '<=', '>', '<','[]'],
            'tags' => ['=@', '==', '@@'],
            'tags_id' => ['=='],
            'notes' => ['=@', '@@'],
            'has_notes' => ['=='],
            'has_manager' => ['=='],
        ]) : null;

        $service->processAttendeesForRSVPInvitation($this->summit_event_id, $this->payload, $filter);

        Log::debug(sprintf("ProcessRSVPInviteesJob::handle summit event id %s has finished", $this->summit_event_id));

    }


}