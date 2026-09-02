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
use App\Services\utils\IEmailExcerptService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\App;
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
    public $timeout = 0;

    public $tries = 1;

    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

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

        $filter = !is_null($this->filter) ? FilterParser::parse($this->filter, \services\model\ISpeakerFilterFields::OPERATORS) : null;

        $service->sendEmails($this->summit_id, $this->payload, $filter);
    }

    /**
     * Invoked by the queue worker once this job is marked failed. With tries = 1 that includes a
     * chunk whose worker was killed mid-run: the job sits reserved until the connection's
     * retry_after elapses, is re-served, and is failed without re-running. Nothing else reports
     * that loss - the outcome excerpt is only sent when sendEmails() runs to completion - so
     * without this hook a dead chunk leaves no trace beyond a queue_failed_jobs row.
     *
     * Log the chunk's speaker ids at error, and when the operator asked for an outcome e-mail
     * send one naming them, so the chunk can be re-sent by id. The chunk is processed one speaker
     * per transaction, so a worker killed mid-run has already e-mailed (and written the "already
     * sent" proof for) the speakers before the kill: the ids are an upper bound on what was lost,
     * not confirmed misses, and both messages say so and tell the operator to re-send with
     * should_resend=false so the resend guard skips the speakers that already have a proof. The
     * excerpt dispatch is best-effort: a failure there must not mask the original failure.
     *
     * @param \Throwable $e
     */
    public function failed(\Throwable $e): void
    {
        $speaker_ids = $this->payload['speaker_ids'] ?? [];
        $flow_event = $this->payload['email_flow_event'] ?? '';
        $ids_list = implode(', ', $speaker_ids);

        $resend_hint = "Re-send these ids with should_resend=false so the speakers already e-mailed are skipped";
        if (isset($this->payload['promo_code_spec'])) {
            // AutomaticMultiSpeakerPromoCodeStrategy::getPromoCode() generates a fresh code on every
            // call, before the resend guard runs, so should_resend=false does not prevent this one.
            $resend_hint .= "; this send auto-generates promo codes and a re-send creates a new code for every speaker in the list, including the ones already e-mailed";
        }

        Log::error
        (
            sprintf
            (
                "ProcessSpeakersEmailRequestJob::failed summit %s flow_event %s: chunk of %s speaker(s) failed (%s: %s); up to %s of them may not have been processed. Speaker ids in the chunk: [%s] filter %s. %s.",
                $this->summit_id,
                $flow_event,
                count($speaker_ids),
                get_class($e),
                $e->getMessage(),
                count($speaker_ids),
                $ids_list,
                json_encode($this->filter),
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

            PresentationSpeakerSelectionProcessExcerptEmail::dispatch($summit, $outcome_email_recipient, $report);
        }
        catch (\Throwable $ex) {
            Log::error($ex);
        }
    }
}
