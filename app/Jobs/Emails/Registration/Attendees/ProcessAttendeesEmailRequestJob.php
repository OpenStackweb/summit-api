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
use App\Jobs\Emails\Registration\Attendees\SummitAttendeeExcerptEmail;
use App\Jobs\Emails\Traits\ResumableChunkJob;
use App\Jobs\Utils\JobDispatcher;
use App\Services\Model\IAttendeeService;
use App\Services\utils\IEmailExcerptService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use models\summit\ISummitRepository;
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

    /**
     * Invoked by the queue worker once this job is marked failed - with ResumableChunkJob's
     * tries = 2 and the resume check inside AttendeeService::send (Task 3), that means BOTH
     * attempts failed: a chunk whose worker was merely killed mid-run (rolling deploy, OOM,
     * scale-down) is re-served and automatically resumed once, skipping only the attendees this
     * run already reached. This hook only fires when that automatic resume itself also failed to
     * finish the chunk. Nothing else reports that loss - the outcome excerpt is only sent when
     * send() runs to completion - so without this hook a dead chunk leaves no trace beyond a
     * queue_failed_jobs row.
     *
     * Log the chunk's attendee ids at error, and when the operator asked for an outcome e-mail
     * send one naming them, so the chunk can be re-sent by id. Mirrors
     * ProcessSpeakersEmailRequestJob::failed(), simplified: attendees generate no promo codes on
     * this path, so there is no should_resend/promo-code resend caveat to add.
     *
     * @param \Throwable $e
     */
    public function failed(\Throwable $e): void
    {
        $attendee_ids = $this->payload['attendees_ids'] ?? [];
        $flow_event = $this->payload['email_flow_event'] ?? '';
        $ids_list = implode(', ', $attendee_ids);

        Log::error
        (
            sprintf
            (
                "ProcessAttendeesEmailRequestJob::failed summit %s flow_event %s: chunk of %s attendee(s) failed (%s: %s); up to %s of them may not have been processed. Attendee ids in the chunk: [%s] filter fields %s.",
                $this->summit_id,
                $flow_event,
                count($attendee_ids),
                get_class($e),
                $e->getMessage(),
                count($attendee_ids),
                $ids_list,
                json_encode($this->redactFilterFieldNames($this->filter))
            )
        );

        $outcome_email_recipient = $this->payload['outcome_email_recipient'] ?? null;
        if (empty($outcome_email_recipient)) return;

        try {
            $summit = App::make(ISummitRepository::class)->getById($this->summit_id);
            if (!$summit instanceof Summit) {
                Log::warning(sprintf("ProcessAttendeesEmailRequestJob::failed summit %s not found, outcome excerpt not sent", $this->summit_id));
                return;
            }

            // Same line types AbstractExcerptEmailJob renders for a completed run, so the
            // operator's inbox reads the same either way.
            $report = [
                [
                    'type' => IEmailExcerptService::InfoType,
                    'message' => sprintf("Processing EMAIL %s for Summit %s", $flow_event, $this->summit_id),
                ],
                [
                    'type' => IEmailExcerptService::ErrorType,
                    'message' => sprintf
                    (
                        "Chunk of %s attendee(s) failed (%s); up to %s of them may not have been processed. Attendee ids in the chunk: %s.",
                        count($attendee_ids),
                        $e->getMessage(),
                        count($attendee_ids),
                        $ids_list
                    ),
                ],
                [
                    'type' => IEmailExcerptService::InfoType,
                    'message' => "TOTAL processed for this chunk is unknown, the job did not run to completion",
                ],
            ];

            // Same failover route as the chunk itself (AttendeeService::triggerSend): a chunk
            // runs on the database fallback worker precisely when the redis primary was down at
            // dispatch time, so a bare ::dispatch() here would throw into the catch below and lose
            // the report in the one scenario it exists for.
            JobDispatcher::withDbFallback(
                job: new SummitAttendeeExcerptEmail($summit, $outcome_email_recipient, $report),
                logContext: ['summit_id' => $this->summit_id, 'attendee_count' => count($attendee_ids)],
                primaryConnection: Config::get('queue.default')
            );
        }
        catch (\Throwable $ex) {
            Log::error($ex);
        }
    }

    /**
     * Reduces a raw filter (["email==foo@bar.com", "first_name==Jane"]) to just its field names
     * (["email", "first_name"]) so error logs never carry filter values that may be PII - email
     * and first_name are valid filter fields (IAttendeeEmailFilterFields::OPERATORS).
     *
     * @param mixed $filter
     * @return string[]
     */
    private function redactFilterFieldNames($filter): array
    {
        if (empty($filter)) return [];
        // FiltersParams::getFilterParam() passes the raw request value through: filter[] arrives
        // as an array, a bare filter= as a string. FilterParser::parse accepts both by wrapping the
        // scalar, so the redaction accepts the same shape instead of dropping the field names.
        if (!is_array($filter)) $filter = [$filter];
        $conditions = array_filter($filter, 'is_scalar');
        return array_values(array_map(fn($condition) => preg_replace('/[=<>@!].*/', '', (string)$condition), $conditions));
    }
}
