<?php namespace Tests;
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

use App\Jobs\Emails\ProcessSpeakersEmailRequestJob;
use Illuminate\Contracts\Queue\Job as QueueJobContract;
use Mockery;
use services\model\ISpeakerService;

/**
 * Covers ProcessSpeakersEmailRequestJob::handle()'s resume activation: on a retry (attempts() >
 * 1) with a dispatched_at already stamped in the payload, handle() must add resume_since =
 * dispatched_at before calling sendEmails(), and must leave should_resend exactly as it arrived
 * in the payload - the resume check and should_resend are independent filters that stack, not
 * one replacing the other.
 *
 * attempts() is driven via InteractsWithQueue::setJob() with a mocked
 * Illuminate\Contracts\Queue\Job, since $this->attempts() returns 1 whenever no job instance is
 * set (dispatchSync, direct calls) and there is no other way to simulate a second delivery
 * without a real queue connection.
 *
 * Class ProcessSpeakersEmailRequestJobResumeTest
 */
class ProcessSpeakersEmailRequestJobResumeTest extends ProtectedApiTestCase
{
    private function jobWithAttempts(array $payload, int $attempts): ProcessSpeakersEmailRequestJob
    {
        $job = new ProcessSpeakersEmailRequestJob(1, $payload, null);

        $queueJob = Mockery::mock(QueueJobContract::class);
        $queueJob->shouldReceive('attempts')->andReturn($attempts);
        $job->setJob($queueJob);

        return $job;
    }

    public function testHandleOnSecondAttemptSetsResumeSinceAndKeepsShouldResendFalse(): void
    {
        $payload = [
            'email_flow_event' => 'SUMMIT_SUBMISSIONS_PRESENTATION_SPEAKER_ACCEPTED_ALTERNATE',
            'speaker_ids'      => [11, 22],
            'dispatched_at'    => 1700000000,
            'should_resend'    => false,
        ];
        $job = $this->jobWithAttempts($payload, 2);

        $service = Mockery::mock(ISpeakerService::class);
        $service->shouldReceive('sendEmails')->once()->withArgs(function ($summit_id, $sentPayload) {
            return ($sentPayload['resume_since'] ?? null) === 1700000000
                && array_key_exists('should_resend', $sentPayload)
                && $sentPayload['should_resend'] === false;
        });

        $job->handle($service);
    }

    public function testHandleOnSecondAttemptKeepsShouldResendTrue(): void
    {
        $payload = [
            'email_flow_event' => 'SUMMIT_SUBMISSIONS_PRESENTATION_SPEAKER_ACCEPTED_ALTERNATE',
            'speaker_ids'      => [11, 22],
            'dispatched_at'    => 1700000000,
            'should_resend'    => true,
        ];
        $job = $this->jobWithAttempts($payload, 2);

        $service = Mockery::mock(ISpeakerService::class);
        $service->shouldReceive('sendEmails')->once()->withArgs(function ($summit_id, $sentPayload) {
            return ($sentPayload['resume_since'] ?? null) === 1700000000
                && $sentPayload['should_resend'] === true;
        });

        $job->handle($service);
    }

    public function testHandleOnFirstAttemptSetsNoResumeSince(): void
    {
        $payload = [
            'email_flow_event' => 'SUMMIT_SUBMISSIONS_PRESENTATION_SPEAKER_ACCEPTED_ALTERNATE',
            'speaker_ids'      => [11, 22],
            'dispatched_at'    => 1700000000,
        ];
        $job = $this->jobWithAttempts($payload, 1);

        $service = Mockery::mock(ISpeakerService::class);
        $service->shouldReceive('sendEmails')->once()->withArgs(function ($summit_id, $sentPayload) {
            return !array_key_exists('resume_since', $sentPayload);
        });

        $job->handle($service);
    }

    public function testHandleOnSecondAttemptWithoutDispatchedAtSetsNoResumeSince(): void
    {
        // A chunk queued by a pod running the previous version of this job (before dispatched_at
        // existed) must not attempt a resume it cannot correctly compute - it retries as a full
        // re-run instead. This covers the deploy-window gap documented in the plan's Fix Approach.
        $payload = [
            'email_flow_event' => 'SUMMIT_SUBMISSIONS_PRESENTATION_SPEAKER_ACCEPTED_ALTERNATE',
            'speaker_ids'      => [11, 22],
        ];
        $job = $this->jobWithAttempts($payload, 2);

        $service = Mockery::mock(ISpeakerService::class);
        $service->shouldReceive('sendEmails')->once()->withArgs(function ($summit_id, $sentPayload) {
            return !array_key_exists('resume_since', $sentPayload);
        });

        $job->handle($service);
    }

    /**
     * Regression guard for ResumableChunkJob's core safety argument: the job's own $timeout must
     * stay strictly below every queue connection's retry_after, or a retried attempt (tries=2)
     * could be re-served to a second worker while the first is still running it - true concurrent
     * execution of the same chunk, which the resume_since check cannot protect against. Nothing
     * else in this test suite asserts this relationship, so a future change to
     * DB_QUEUE_RETRY_AFTER/REDIS_RETRY_AFTER (or to ResumableChunkJob::$timeout) that violates it
     * would otherwise go uncaught.
     */
    public function testTimeoutStaysStrictlyBelowRetryAfterForEveryQueueConnection(): void
    {
        $job = new ProcessSpeakersEmailRequestJob(1, [], null);

        $this->assertLessThan(
            config('queue.connections.database.retry_after'),
            $job->timeout,
            'job timeout must stay strictly below the database queue retry_after, or a retried attempt can run concurrently with a still-live earlier attempt'
        );
        $this->assertLessThan(
            config('queue.connections.redis.retry_after'),
            $job->timeout,
            'job timeout must stay strictly below the redis queue retry_after, or a retried attempt can run concurrently with a still-live earlier attempt'
        );
    }
}
