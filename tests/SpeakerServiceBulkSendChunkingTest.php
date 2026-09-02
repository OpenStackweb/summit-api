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
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Queue;
use models\main\Member;
use models\summit\PresentationSpeaker;
use ReflectionObject;
use services\model\ISpeakerService;

/**
 * Covers SpeakerService::triggerSendEmails's chunking behaviour: it resolves the full set of
 * matched speaker ids synchronously and dispatches one ProcessSpeakersEmailRequestJob per
 * SpeakerService::CHUNK_SIZE-sized group, instead of a single job the trait pages through.
 *
 * Most cases use an explicit speaker_ids payload with fabricated ids rather than real, seeded
 * speakers: Queue::fake() intercepts dispatch before the job's handle() ever runs, and the
 * explicit-ids path never queries the repository, so the ids don't need to correspond to real
 * entities. Only the filter-based-selection and member_id cases need real, DB-backed speakers,
 * because those exercise the actual paginated query.
 *
 * Class SpeakerServiceBulkSendChunkingTest
 */
class SpeakerServiceBulkSendChunkingTest extends ProtectedApiTestCase
{
    use InsertSummitTestData;

    protected function setUp(): void
    {
        parent::setUp();
        self::insertSummitTestData();
    }

    protected function tearDown(): void
    {
        self::clearSummitTestData();
        parent::tearDown();
    }

    private function service(): ISpeakerService
    {
        return App::make(ISpeakerService::class);
    }

    private function basePayload(): array
    {
        return ['email_flow_event' => 'ACCEPTED_ALTERNATE'];
    }

    /**
     * @return ProcessSpeakersEmailRequestJob[]
     */
    private function pushedJobs(): array
    {
        $jobs = [];
        Queue::assertPushed(ProcessSpeakersEmailRequestJob::class, function ($job) use (&$jobs) {
            $jobs[] = $job;
            return true;
        });
        return $jobs;
    }

    private function jobProperty(ProcessSpeakersEmailRequestJob $job, string $name)
    {
        $reflection = new ReflectionObject($job);
        $property = $reflection->getProperty($name);
        $property->setAccessible(true);
        return $property->getValue($job);
    }

    public function testDispatchesOneChunkPerHundredIdsWithNoOverlap(): void
    {
        Queue::fake();
        $ids = range(1, 250);
        $payload = $this->basePayload();
        $payload['speaker_ids'] = $ids;

        $this->service()->triggerSendEmails(self::$summit, $payload, null);

        Queue::assertPushed(ProcessSpeakersEmailRequestJob::class, 3);

        $jobs = $this->pushedJobs();
        $slices = array_map(fn($job) => $this->jobProperty($job, 'payload')['speaker_ids'], $jobs);

        $this->assertCount(100, $slices[0]);
        $this->assertCount(100, $slices[1]);
        $this->assertCount(50, $slices[2]);

        $covered = array_merge(...$slices);
        sort($covered);
        $this->assertEquals($ids, $covered, 'the slices together must cover every matched id exactly once');
    }

    public function testDispatchesExactlyOneJobWhenMatchedCountEqualsChunkSize(): void
    {
        Queue::fake();
        $ids = range(1, 100);
        $payload = $this->basePayload();
        $payload['speaker_ids'] = $ids;

        $this->service()->triggerSendEmails(self::$summit, $payload, null);

        Queue::assertPushed(ProcessSpeakersEmailRequestJob::class, 1);
    }

    public function testDispatchesNothingWhenSpeakerIdsIsEmpty(): void
    {
        Queue::fake();
        $payload = $this->basePayload();
        $payload['speaker_ids'] = [];

        $this->service()->triggerSendEmails(self::$summit, $payload, null);

        Queue::assertNotPushed(ProcessSpeakersEmailRequestJob::class);
    }

    public function testExcludedSpeakerIdsAreRemovedBeforeChunking(): void
    {
        Queue::fake();
        $payload = $this->basePayload();
        $payload['speaker_ids'] = range(1, 10);
        $payload['excluded_speaker_ids'] = [3, 7];

        $this->service()->triggerSendEmails(self::$summit, $payload, null);

        Queue::assertPushed(ProcessSpeakersEmailRequestJob::class, 1);
        $jobs = $this->pushedJobs();
        $slice = $this->jobProperty($jobs[0], 'payload')['speaker_ids'];
        sort($slice);

        $this->assertEquals([1, 2, 4, 5, 6, 8, 9, 10], $slice);
        $this->assertArrayNotHasKey(
            'excluded_speaker_ids',
            $this->jobProperty($jobs[0], 'payload'),
            'excluded_speaker_ids must not be carried forward once already applied'
        );
    }

    public function testDuplicateExplicitIdsAreDedupedBeforeDispatch(): void
    {
        Queue::fake();
        $payload = $this->basePayload();
        $payload['speaker_ids'] = [5, 5, 6];

        $this->service()->triggerSendEmails(self::$summit, $payload, null);

        Queue::assertPushed(ProcessSpeakersEmailRequestJob::class, 1);
        $jobs = $this->pushedJobs();
        $slice = $this->jobProperty($jobs[0], 'payload')['speaker_ids'];
        sort($slice);

        $this->assertEquals([5, 6], $slice, 'a duplicate id must only appear once across all dispatched jobs');
    }

    public function testExplicitFilterRawValueReachesEveryChunkUnchanged(): void
    {
        Queue::fake();
        $payload = $this->basePayload();
        $payload['speaker_ids'] = range(1, 150);
        $rawFilter = ['first_name=@Test'];

        $this->service()->triggerSendEmails(self::$summit, $payload, $rawFilter);

        Queue::assertPushed(ProcessSpeakersEmailRequestJob::class, 2);
        foreach ($this->pushedJobs() as $job) {
            $this->assertSame(
                $rawFilter,
                $this->jobProperty($job, 'filter'),
                "every chunk's private filter constructor argument must be identical (===) to the raw filter triggerSendEmails received"
            );
        }
    }

    public function testChunkPayloadCarriesOtherPayloadKeysThrough(): void
    {
        Queue::fake();
        $payload = [
            'email_flow_event' => 'ACCEPTED_ALTERNATE',
            'speaker_ids' => [1, 2, 3],
            'original_filter' => ['id==1||2||3'],
            'promo_code_spec' => ['class_name' => 'SPEAKERS_PROMO_CODE', 'type' => 'ACCEPTED'],
            'test_email_recipient' => 'test@example.com',
            'outcome_email_recipient' => 'outcome@example.com',
            'should_resend' => false,
            'should_send_copy_2_submitter' => true,
        ];

        $this->service()->triggerSendEmails(self::$summit, $payload, null);

        Queue::assertPushed(ProcessSpeakersEmailRequestJob::class, 1);
        $jobs = $this->pushedJobs();
        $chunkPayload = $this->jobProperty($jobs[0], 'payload');

        $this->assertEquals($payload['original_filter'], $chunkPayload['original_filter']);
        $this->assertEquals($payload['promo_code_spec'], $chunkPayload['promo_code_spec']);
        $this->assertEquals($payload['test_email_recipient'], $chunkPayload['test_email_recipient']);
        $this->assertEquals($payload['outcome_email_recipient'], $chunkPayload['outcome_email_recipient']);
        $this->assertEquals($payload['should_resend'], $chunkPayload['should_resend']);
        $this->assertEquals($payload['should_send_copy_2_submitter'], $chunkPayload['should_send_copy_2_submitter']);
        $this->assertEquals($payload['email_flow_event'], $chunkPayload['email_flow_event']);
        $this->assertEquals([1, 2, 3], $chunkPayload['speaker_ids']);
    }

    public function testFilterBasedSelectionResolvesRealMatchingSpeakersAndChunks(): void
    {
        Queue::fake();

        // self::$defaultSpeaker already has 20 presentations attached to self::$summit
        // (via InsertSummitTestData), so it matches getSpeakersIdsBySummit with no filter.
        $payload = $this->basePayload();

        $this->service()->triggerSendEmails(self::$summit, $payload, null);

        Queue::assertPushed(ProcessSpeakersEmailRequestJob::class, 1);
        $jobs = $this->pushedJobs();
        $chunkPayload = $this->jobProperty($jobs[0], 'payload');

        $this->assertContains(
            self::$defaultSpeaker->getId(),
            $chunkPayload['speaker_ids'],
            'a speaker with a presentation in this summit must be selected by the filter-based path'
        );
        $this->assertNull(
            $this->jobProperty($jobs[0], 'filter'),
            'the raw filter (null, in this case) must still reach the dispatched job unchanged'
        );
    }

    public function testMemberIdFilterSelectsOnlyTheMatchingSpeaker(): void
    {
        Queue::fake();

        $prefix = str_random(10);
        $member = new Member();
        $member->setEmail("chunk-test+{$prefix}@test.com");
        $member->setActive(true);
        $member->setFirstName("Chunk");
        $member->setLastName("Test");
        $member->setEmailVerified(true);
        $member->setUserExternalId(mt_rand());
        self::$em->persist($member);

        $memberSpeaker = new PresentationSpeaker();
        $memberSpeaker->setFirstName("Chunk");
        $memberSpeaker->setLastName("Test Speaker");
        $memberSpeaker->setMember($member);
        self::$em->persist($memberSpeaker);

        $presentation = new \models\summit\Presentation();
        self::$summit->addEvent($presentation);
        $presentation->setTitle("Chunk test presentation {$prefix}");
        $presentation->setAbstract("Abstract {$prefix}");
        $presentation->setCategory(self::$defaultTrack);
        $presentation->setType(self::$defaultPresentationType);
        $presentation->setStartDate(new \DateTime('now', new \DateTimeZone('UTC')));
        $presentation->setEndDate(new \DateTime('+1 hour', new \DateTimeZone('UTC')));
        $presentation->addSpeaker($memberSpeaker);
        self::$em->persist($presentation);

        self::$em->flush();

        $rawFilter = ['member_id==' . $member->getId()];

        $payload = $this->basePayload();

        $this->service()->triggerSendEmails(self::$summit, $payload, $rawFilter);

        Queue::assertPushed(ProcessSpeakersEmailRequestJob::class, 1);
        $jobs = $this->pushedJobs();
        $chunkPayload = $this->jobProperty($jobs[0], 'payload');

        $this->assertEquals(
            [$memberSpeaker->getId()],
            $chunkPayload['speaker_ids'],
            'member_id must select only the speaker belonging to that member, not the default fixture speaker'
        );
    }

    public function testOneChunkFailingAllFallbackTiersDoesNotAbortSiblingChunks(): void
    {
        // Per-chunk failure isolation: JobDispatcher::withDbFallback tries the primary
        // connection, the database fallback, and a synchronous run. When ALL THREE fail
        // for a chunk, the per-chunk try/catch must log at error level and keep the loop
        // going, so a bad chunk cannot also block every sibling chunk. Forcing every Bus
        // dispatch to throw makes all 3 chunks fail through all 3 tiers; one Log::error
        // per chunk proves the loop reached every chunk instead of aborting on the first.
        \Illuminate\Support\Facades\Bus::shouldReceive('dispatch')
            ->andThrow(new \RuntimeException('queue backend down'));
        \Illuminate\Support\Facades\Bus::shouldReceive('dispatchSync')
            ->andThrow(new \RuntimeException('sync run failed'));
        \Illuminate\Support\Facades\Log::spy();

        $payload = $this->basePayload();
        $payload['speaker_ids'] = range(1, 250); // 3 chunks

        $this->service()->triggerSendEmails(self::$summit, $payload, null);

        // At least one Log::error per chunk (JobDispatcher logs its own on the database
        // fallback failing, plus the per-chunk catch). If the try/catch moved outside the
        // loop, only the first chunk would ever be attempted (< 3 errors); if the catch
        // were removed, the exception would propagate and fail this test outright.
        \Illuminate\Support\Facades\Log::shouldHaveReceived('error')->atLeast()->times(3);
    }
}
