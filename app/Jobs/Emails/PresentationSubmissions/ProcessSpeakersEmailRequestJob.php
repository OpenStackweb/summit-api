<?php namespace App\Jobs\Emails;
/**
 * Copyright 2022 OpenStack Foundation
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
use App\Jobs\Emails\PresentationSubmissions\SelectionProcess\PresentationSpeakerSelectionProcessExcerptEmail;
use App\Jobs\Emails\Traits\ResumableChunkJob;
use App\Jobs\Utils\JobDispatcher;
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
use services\model\ISpeakerService;
use utils\FilterParser;
/**
 * Class ProcessSpeakersEmailRequestJob
 * @package App\Jobs\Emails
 */
final class ProcessSpeakersEmailRequestJob implements ShouldQueue
{
    // $timeout/$tries/$backoff and the resume-on-retry mechanics come from ResumableChunkJob -
    // see that trait's doc comment for why timeout must stay below every retry_after / worker
    // --timeout this job can run under.
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, ResumableChunkJob;

    /**
     * @var int
     */
    private $summit_id;

    /**
     * @var array
     */
    private $payload;

    /**
     * @var mixed
     */
    private $filter;

    /**
     * ProcessSpeakersEmailRequestJob constructor.
     * @param int $summit_id
     * @param array $payload
     * @param $filter
     */
    public function __construct(int $summit_id, array $payload, $filter)
    {
        $this->summit_id = $summit_id;
        $this->payload = $payload;
        $this->filter = $filter;
    }

    /**
     * @param ISummitRepository $summit_repository
     * @param ISpeakerService $service
     * @throws \utils\FilterParserException
     */
    public function handle
    (
        ISpeakerService $service
    ){
        Log::debug
        (
            sprintf
            (
                "ProcessSpeakersEmailRequestJob::handle summit id %s payload %s",
                $this->summit_id,
                json_encode($this->payload)
            )
        );

        // ResumableChunkJob::activateResumeIfRetrying(): resume, not resend. On a retry it sets
        // resume_since = dispatched_at in $this->payload so the service skips only the speakers
        // whose proof for this email type was written by THIS run. should_resend is left exactly
        // as it arrived - it answers a different question (does the operator want to re-email
        // speakers with a proof from any earlier campaign?) and the two filters stack.
        $this->activateResumeIfRetrying();

        $filter = !is_null($this->filter) ? FilterParser::parse($this->filter, \services\model\ISpeakerFilterFields::OPERATORS) : null;

        $service->sendEmails($this->summit_id, $this->payload, $filter);
    }

    /**
     * Invoked by the queue worker once this job is marked failed - with tries = 2 and a resume
     * check on the retry (see handle()), that means BOTH attempts failed: a chunk whose worker
     * was merely killed mid-run (rolling deploy, OOM, scale-down) is re-served and automatically
     * resumed once, skipping only the speakers this run already reached. This hook only fires
     * when that automatic resume itself also failed to finish the chunk. Nothing else reports
     * that loss - the outcome excerpt is only sent when sendEmails() runs to completion - so
     * without this hook a dead chunk leaves no trace beyond a queue_failed_jobs row.
     *
     * Log the chunk's speaker ids at error, and when the operator asked for an outcome e-mail
     * send one naming them, so the chunk can be re-sent by id. The chunk is processed one speaker
     * per transaction, so a worker killed mid-run has already e-mailed (and written the "already
     * sent" proof for) some of the speakers before the kill: the ids are an upper bound on what
     * was lost, not confirmed misses, and both messages say so. should_resend=false is NOT a
     * blanket recommendation here: hasAnnouncementEmailTypeSent (the check it gates) carries no
     * date, so it also skips every speaker with a proof from any EARLIER, unrelated campaign of
     * the same type - the hint below warns about that rather than recommending it outright. The
     * excerpt goes through JobDispatcher::withDbFallback (primary, then the database queue, then
     * an inline run) and is best-effort: a failure there must not mask the original failure.
     *
     * @param \Throwable $e
     */
    public function failed(\Throwable $e): void
    {
        $speaker_ids = $this->payload['speaker_ids'] ?? [];
        $flow_event = $this->payload['email_flow_event'] ?? '';
        $ids_list = implode(', ', $speaker_ids);

        $resend_hint = "Both automatic attempts are exhausted. A manual re-send of these ids with should_resend=false skips any speaker who already has a proof of this email type - not just from this run, but from ANY earlier campaign of the same type - so use it to avoid duplicating what this run already sent, never to re-run a deliberate second campaign, or the first campaign's speakers get silently skipped too";
        if (isset($this->payload['promo_code_spec'])) {
            // AutomaticMultiSpeakerPromoCodeStrategy::getPromoCode() generates a fresh code on every
            // call, before the resend guard runs, so should_resend=false does not prevent this one.
            $resend_hint .= "; this send auto-generates promo codes and a re-send creates a new code for every speaker in the list, including the ones already e-mailed";
        }

        Log::error
        (
            sprintf
            (
                "ProcessSpeakersEmailRequestJob::failed summit %s flow_event %s: chunk of %s speaker(s) failed (%s: %s); up to %s of them may not have been processed. Speaker ids in the chunk: [%s] filter fields %s. %s.",
                $this->summit_id,
                $flow_event,
                count($speaker_ids),
                get_class($e),
                $e->getMessage(),
                count($speaker_ids),
                $ids_list,
                json_encode($this->redactFilterFieldNames($this->filter)),
                $resend_hint
            )
        );

        $outcome_email_recipient = $this->payload['outcome_email_recipient'] ?? null;
        if (empty($outcome_email_recipient)) return;

        try {
            $summit = App::make(ISummitRepository::class)->getById($this->summit_id);
            if (!$summit instanceof Summit) {
                Log::warning(sprintf("ProcessSpeakersEmailRequestJob::failed summit %s not found, outcome excerpt not sent", $this->summit_id));
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
                        "Chunk of %s speaker(s) failed (%s); up to %s of them may not have been processed. Speaker ids in the chunk: %s. %s",
                        count($speaker_ids),
                        $e->getMessage(),
                        count($speaker_ids),
                        $ids_list,
                        $resend_hint
                    ),
                ],
                [
                    'type' => IEmailExcerptService::InfoType,
                    'message' => "TOTAL processed for this chunk is unknown, the job did not run to completion",
                ],
            ];

            // Same failover route as the chunk itself (SpeakerService::triggerSendEmails): a chunk
            // runs on the database fallback worker precisely when the redis primary was down at
            // dispatch time, so a bare ::dispatch() here would throw into the catch below and lose
            // the report in the one scenario it exists for.
            JobDispatcher::withDbFallback(
                job: new PresentationSpeakerSelectionProcessExcerptEmail($summit, $outcome_email_recipient, $report),
                logContext: ['summit_id' => $this->summit_id, 'speaker_count' => count($speaker_ids)],
                primaryConnection: Config::get('queue.default')
            );
        }
        catch (\Throwable $ex) {
            Log::error($ex);
        }
    }

    /**
     * Reduces a raw filter (["email==foo@bar.com", "presentations_selection_plan_id==78"]) to
     * just its field names (["email", "presentations_selection_plan_id"]) so error logs never
     * carry filter values that may be PII - email and full_name are valid filter fields
     * (ISpeakerFilterFields::OPERATORS).
     *
     * @param mixed $filter
     * @return string[]
     */
    private function redactFilterFieldNames($filter): array
    {
        if (empty($filter) || !is_array($filter)) return [];
        return array_map(fn($condition) => preg_replace('/[=<>@!].*/', '', (string)$condition), $filter);
    }
}
