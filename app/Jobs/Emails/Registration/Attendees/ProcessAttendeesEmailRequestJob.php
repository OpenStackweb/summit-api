<?php namespace App\Jobs\Emails;
/**
 * Copyright 2020 OpenStack Foundation
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
use App\Jobs\Emails\Traits\ResumableChunkJob;
use App\Services\Model\IAttendeeService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use models\summit\Summit;
use services\model\IAttendeeEmailFilterFields;
use utils\FilterParser;
/**
 * Class ProcessAttendeesEmailRequestJob
 * @package App\Jobs\Emails
 */
final class ProcessAttendeesEmailRequestJob implements ShouldQueue
{
    // $timeout/$tries/$backoff and the resume-on-retry mechanics come from ResumableChunkJob -
    // see that trait's doc comment for why timeout must stay below every retry_after / worker
    // --timeout this job can run under.
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, ResumableChunkJob;

    private $summit_id;

    private $payload;

    private $filter;

    /**
     * ProcessAttendeesEmailRequestJob constructor.
     * @param Summit $summit
     * @param array $payload
     * @param $filter
     */
    public function __construct(Summit $summit, array $payload, $filter)
    {
        $this->summit_id = $summit->getId();
        $this->payload = $payload;
        $this->filter = $filter;
    }

    public function handle(IAttendeeService $service){
        Log::debug
        (
            sprintf
            (
                "ProcessAttendeesEmailRequestJob::handle summit id %s payload %s filter %s",
                $this->summit_id,
                json_encode($this->payload),
                json_encode($this->filter)
            )
        );

        // ResumableChunkJob::activateResumeIfRetrying(): resume, not resend. On a retry it sets
        // resume_since = dispatched_at in $this->payload so AttendeeService::send skips only the
        // attendees (or, for the ticket flow event, attendee+ticket pairs) whose proof for this
        // run was written by THIS run.
        $this->activateResumeIfRetrying();

        $filter = !is_null($this->filter) ? FilterParser::parse($this->filter, IAttendeeEmailFilterFields::OPERATORS) : null;

        $service->send($this->summit_id, $this->payload, $filter);
    }

}
