<?php namespace App\Jobs\Emails\Traits;
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
use Illuminate\Support\Facades\Log;

/**
 * Trait ResumableChunkJob
 *
 * Shared retry/resume mechanics for a bulk-email chunk job dispatched by a *Service::sendEmails
 * chunk loop (ProcessSpeakersEmailRequestJob / SpeakerService::triggerSendEmails is the first
 * caller). Bounds the job's own runtime strictly below every queue connection's retry_after so a
 * retried attempt can never run concurrently with a still-live earlier attempt - timeout = 0
 * would NOT mean "no job-level bound, use the worker's": Laravel writes the job's $timeout
 * property into the queue payload, Worker::timeoutForJob prefers it over the worker option
 * because 0 is not null, and registerTimeoutHandler then calls pcntl_alarm(0), which cancels the
 * alarm outright. Without an explicit bound below retry_after, a hung (not killed, just slow)
 * chunk could outlive retry_after and be re-served to a second worker while the first is still
 * running it - true concurrent execution of the same chunk, which the resume check below cannot
 * protect against. Retries once with a backoff so a deterministic failure does not retry
 * instantly, and on that retry exposes resume_since = dispatched_at in the payload so the
 * receiving *Service::sendEmails can skip only the recipients this run already reached. Every
 * other payload key - notably should_resend - is left exactly as it arrived: the resume check and
 * should_resend answer different questions (did THIS run already reach this recipient, vs. does
 * the operator want to re-send to anyone with a proof from any past campaign) and stack rather
 * than replace one another.
 *
 * Composing job must:
 *  - implement Illuminate\Contracts\Queue\ShouldQueue and use
 *    Illuminate\Queue\InteractsWithQueue (this trait's attempts() call comes from there);
 *  - hold the dispatch payload in a $payload array property, stamped with a dispatched_at key
 *    (UTC epoch, once per run) by the *Service::triggerSendEmails chunk loop that dispatches it;
 *  - call activateResumeIfRetrying() at the top of handle(), before handing $this->payload to the
 *    receiving service.
 *
 * A chunk with no dispatched_at (queued by a pod running a version of the composing job from
 * before this trait existed) is left untouched on a retry - it retries as a full re-run rather
 * than an incorrect resume computed from a missing timestamp. That is a bounded, accepted risk
 * (one duplicate chunk) limited to a single rolling-deploy window.
 */
trait ResumableChunkJob
{
    // 1200s: strictly below every queue connection's retry_after (1800 on both redis and
    // database, config/queue.php) and the database-fallback worker's --timeout=1400
    // (fn-docker/summit-api/php-entry-point.sh). Composing jobs sharing this trait must not
    // override this to a value at or above the lowest retry_after / worker --timeout they run
    // under, or the concurrency guarantee above no longer holds.
    public $timeout = 1200;

    public $tries = 2;

    public $backoff = 300;

    /**
     * Mutates $this->payload in place: on a retry (attempts() > 1) with a dispatched_at already
     * stamped, sets resume_since = dispatched_at and logs a warning. First attempt, or a chunk
     * with no dispatched_at, leaves the payload untouched.
     */
    private function activateResumeIfRetrying(): void
    {
        if ($this->attempts() <= 1 || !isset($this->payload['dispatched_at'])) {
            return;
        }

        $this->payload['resume_since'] = $this->payload['dispatched_at'];

        Log::warning
        (
            sprintf
            (
                "%s::handle attempt %s: resuming chunk from dispatched_at %s",
                static::class,
                $this->attempts(),
                date('c', $this->payload['dispatched_at'])
            )
        );
    }
}
