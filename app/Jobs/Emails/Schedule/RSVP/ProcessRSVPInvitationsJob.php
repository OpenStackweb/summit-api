<?php namespace App\Jobs\Emails\Schedule\RSVP;
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

class ProcessRSVPInvitationsJob implements ShouldQueue
{
    public $tries = 1;

    // no timeout
    public $timeout = 0;

    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $payload;

    private $filter;

    private $summit_event_id;

    /**
     * ProcessRSVPInvitationsJob constructor.
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

        Log::debug(sprintf("ProcessRSVPInvitationsJob::handle summit event id %s", $this->summit_event_id));

        $filter = !is_null($this->filter) ? FilterParser::parse($this->filter, [
            'id' => ['=='],
            'not_id' => ['=='],
            'attendee_email' => ['@@','=@', '=='],
            'attendee_first_name' => ['@@','=@', '=='],
            'attendee_last_name' => ['@@','=@', '=='],
            'attendee_full_name' => ['@@','=@', '=='],
            'is_accepted' => ['=='],
            'is_sent' => ['=='],
            'status' => ['=='],
        ]) : null;

        $service->send($this->summit_event_id, $this->payload, $filter);

        Log::debug(sprintf("ProcessRSVPInvitationsJob::handle summit event id %s has finished", $this->summit_event_id));

    }

}