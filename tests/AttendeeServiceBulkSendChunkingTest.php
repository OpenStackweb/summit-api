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

use App\Jobs\Emails\ProcessAttendeesEmailRequestJob;
use App\Models\Foundation\Main\IGroup;
use App\Services\Model\IAttendeeService;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Queue;
use ReflectionObject;

/**
 * Covers AttendeeService::triggerSend's chunking behaviour: it resolves the full set of matched
 * attendee ids synchronously and dispatches one ProcessAttendeesEmailRequestJob per
 * emails.attendees_process_job_chunk_size-sized group, instead of the single unbounded job
 * dispatch it used before Task 4.
 *
 * Most cases use an explicit attendees_ids payload with fabricated ids rather than real, seeded
 * attendees: Queue::fake() intercepts dispatch before the job's handle() ever runs, and the
 * explicit-ids path never queries the repository, so the ids don't need to correspond to real
 * entities. Only the filter-based-selection case needs real, DB-backed attendees, because that
 * exercises the actual paginated query (and, with it, Task 1's ordering fix).
 *
 * Class AttendeeServiceBulkSendChunkingTest
 */
final class AttendeeServiceBulkSendChunkingTest extends TestCase
{
    use InsertSummitTestData;

    use InsertMemberTestData;

    protected function setUp(): void
    {
        parent::setUp();
        self::insertMemberTestData(IGroup::TrackChairs);
        self::$defaultMember = self::$member;
        self::insertSummitTestData();
    }

    protected function tearDown(): void
    {
        self::clearSummitTestData();
        self::clearMemberTestData();
        parent::tearDown();
    }

    private function service(): IAttendeeService
    {
        return App::make(IAttendeeService::class);
    }

    private function basePayload(): array
    {
        return ['email_flow_event' => 'SUMMIT_REGISTRATION_GENERIC_ATTENDEE_EMAIL'];
    }

    /**
     * @return ProcessAttendeesEmailRequestJob[]
     */
    private function pushedJobs(): array
    {
        $jobs = [];
        Queue::assertPushed(ProcessAttendeesEmailRequestJob::class, function ($job) use (&$jobs) {
            $jobs[] = $job;
            return true;
        });
        return $jobs;
    }

    private function jobProperty(ProcessAttendeesEmailRequestJob $job, string $name)
    {
        $reflection = new ReflectionObject($job);
        $property = $reflection->getProperty($name);
        $property->setAccessible(true);
        return $property->getValue($job);
    }

    public function testDispatchesOneChunkPerConfiguredSizeWithNoOverlap(): void
    {
        Queue::fake();
        $chunkSize = intval(Config::get('emails.attendees_process_job_chunk_size', 2000));
        $remainder = 50;
        $ids = range(1, 2 * $chunkSize + $remainder);
        $payload = $this->basePayload();
        $payload['attendees_ids'] = $ids;

        $this->service()->triggerSend(self::$summit, $payload, null);

        Queue::assertPushed(ProcessAttendeesEmailRequestJob::class, 3);

        $jobs = $this->pushedJobs();
        $slices = array_map(fn($job) => $this->jobProperty($job, 'payload')['attendees_ids'], $jobs);

        $this->assertCount($chunkSize, $slices[0]);
        $this->assertCount($chunkSize, $slices[1]);
        $this->assertCount($remainder, $slices[2]);

        $reassembled = array_merge($slices[0], $slices[1], $slices[2]);
        sort($reassembled);
        $this->assertSame($ids, $reassembled, 'chunks must partition the id set with no overlap and no gap');
    }

    public function testDispatchesExactlyOneJobWhenMatchedCountEqualsChunkSize(): void
    {
        Queue::fake();
        $chunkSize = intval(Config::get('emails.attendees_process_job_chunk_size', 2000));
        $payload = $this->basePayload();
        $payload['attendees_ids'] = range(1, $chunkSize);

        $this->service()->triggerSend(self::$summit, $payload, null);

        Queue::assertPushed(ProcessAttendeesEmailRequestJob::class, 1);
    }

    public function testDispatchesNothingWhenAttendeeIdsIsEmpty(): void
    {
        Queue::fake();
        $payload = $this->basePayload();
        $payload['attendees_ids'] = [];

        $this->service()->triggerSend(self::$summit, $payload, null);

        Queue::assertNothingPushed();
    }

    public function testExcludedAttendeeIdsAreRemovedBeforeChunking(): void
    {
        Queue::fake();
        $payload = $this->basePayload();
        $payload['attendees_ids'] = [1, 2, 3, 4, 5];
        $payload['excluded_attendees_ids'] = [2, 4];

        $this->service()->triggerSend(self::$summit, $payload, null);

        $jobs = $this->pushedJobs();
        $this->assertCount(1, $jobs);
        $ids = $this->jobProperty($jobs[0], 'payload')['attendees_ids'];
        sort($ids);
        $this->assertSame([1, 3, 5], $ids);

        // excluded_attendees_ids must not leak into the chunk payload itself
        $this->assertArrayNotHasKey('excluded_attendees_ids', $this->jobProperty($jobs[0], 'payload'));
    }

    public function testDuplicateExplicitIdsAreDedupedBeforeDispatch(): void
    {
        Queue::fake();
        $payload = $this->basePayload();
        $payload['attendees_ids'] = [1, 2, 2, 3, 1];

        $this->service()->triggerSend(self::$summit, $payload, null);

        $jobs = $this->pushedJobs();
        $this->assertCount(1, $jobs);
        $ids = $this->jobProperty($jobs[0], 'payload')['attendees_ids'];
        sort($ids);
        $this->assertSame([1, 2, 3], $ids);
    }

    public function testChunkPayloadCarriesOtherPayloadKeysThroughAndStripsCallerSuppliedResumeSince(): void
    {
        Queue::fake();
        $payload = $this->basePayload();
        $payload['attendees_ids'] = [1, 2, 3];
        $payload['outcome_email_recipient'] = 'ops@example.com';
        $payload['resume_since'] = 123456; // caller-supplied - must never reach a first-attempt chunk

        $this->service()->triggerSend(self::$summit, $payload, null);

        $jobs = $this->pushedJobs();
        $chunkPayload = $this->jobProperty($jobs[0], 'payload');

        $this->assertSame('ops@example.com', $chunkPayload['outcome_email_recipient']);
        $this->assertArrayHasKey('dispatched_at', $chunkPayload);
        $this->assertArrayNotHasKey('resume_since', $chunkPayload);
    }

    public function testFilterBasedSelectionResolvesRealMatchingAttendeesAndChunks(): void
    {
        Queue::fake();
        $payload = $this->basePayload();
        // no attendees_ids - triggers the paginated getAllIdsByPage path

        $this->service()->triggerSend(self::$summit, $payload, null);

        $jobs = $this->pushedJobs();
        $this->assertGreaterThanOrEqual(1, count($jobs));

        $ids = [];
        foreach ($jobs as $job) {
            $ids = array_merge($ids, $this->jobProperty($job, 'payload')['attendees_ids']);
        }
        sort($ids);

        $expected = [];
        foreach (self::$summit->getAttendees() as $attendee) {
            $expected[] = $attendee->getId();
        }
        sort($expected);

        $this->assertSame($expected, $ids, 'every fixture attendee must be covered exactly once');
    }

    public function testOneChunkFailingAllFallbackTiersDoesNotAbortSiblingChunks(): void
    {
        // Per-chunk failure isolation: JobDispatcher::withDbFallback tries the primary
        // connection, the database fallback, and a synchronous run. When ALL THREE fail for a
        // chunk, the per-chunk try/catch must log at error level and keep the loop going, so a
        // bad chunk cannot also block every sibling chunk. Forcing every Bus dispatch to throw
        // makes every chunk fail through all 3 tiers; one Log::error per chunk proves the loop
        // reached every chunk instead of aborting on the first.
        Config::set('emails.attendees_process_job_chunk_size', 100);

        \Illuminate\Support\Facades\Bus::shouldReceive('dispatch')
            ->andThrow(new \RuntimeException('queue backend down'));
        \Illuminate\Support\Facades\Bus::shouldReceive('dispatchSync')
            ->andThrow(new \RuntimeException('sync run failed'));
        \Illuminate\Support\Facades\Log::spy();

        $payload = $this->basePayload();
        $payload['attendees_ids'] = range(1, 250); // 3 chunks of 100/100/50

        $this->service()->triggerSend(self::$summit, $payload, null);

        // At least one Log::error per chunk (JobDispatcher logs its own on the database
        // fallback failing, plus the per-chunk catch). If the try/catch moved outside the
        // loop, only the first chunk would ever be attempted (< 3 errors); if the catch
        // were removed, the exception would propagate and fail this test outright.
        \Illuminate\Support\Facades\Log::shouldHaveReceived('error')->atLeast()->times(3);
    }
}
