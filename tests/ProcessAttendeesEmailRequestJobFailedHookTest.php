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

use App\Jobs\Emails\IMailTemplatesConstants;
use App\Jobs\Emails\ProcessAttendeesEmailRequestJob;
use App\Jobs\Emails\Registration\Attendees\SummitAttendeeExcerptEmail;
use App\Models\Foundation\Main\IGroup;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Mockery;
use ReflectionObject;

/**
 * Covers ProcessAttendeesEmailRequestJob::failed(), the hook the queue worker invokes once a
 * chunk job is marked failed (ResumableChunkJob: tries = 2, so this only fires once both the
 * original attempt and the automatic resume have failed). The outcome excerpt is otherwise only
 * sent when send() runs to completion, so this hook is the only thing that tells the operator
 * which attendee ids never got their email.
 *
 * The hook is invoked directly here, mirroring ProcessSpeakersEmailRequestJobFailedHookTest:
 * Queue::fake() intercepts dispatch before handle() runs, so the framework's fail() -> failed()
 * plumbing can't be driven end to end without a real queue connection. What this class pins is
 * what OUR hook does.
 *
 * Class ProcessAttendeesEmailRequestJobFailedHookTest
 */
final class ProcessAttendeesEmailRequestJobFailedHookTest extends TestCase
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

    private function jobProperty(object $job, string $name)
    {
        $reflection = new ReflectionObject($job);
        while ($reflection && !$reflection->hasProperty($name)) {
            $reflection = $reflection->getParentClass();
        }
        $property = $reflection->getProperty($name);
        $property->setAccessible(true);
        return $property->getValue($job);
    }

    public function testFailedChunkWithOutcomeRecipientSendsExcerptNamingTheUnprocessedIds(): void
    {
        Queue::fake();
        Log::spy();

        $job = new ProcessAttendeesEmailRequestJob(self::$summit, [
            'email_flow_event' => 'SUMMIT_REGISTRATION_GENERIC_ATTENDEE_EMAIL',
            'attendees_ids' => [11, 22, 33],
            'outcome_email_recipient' => 'outcome@example.com',
        ], null);

        $job->failed(new \RuntimeException('worker killed mid-chunk'));

        Queue::assertPushed(SummitAttendeeExcerptEmail::class, 1);
        Queue::assertPushed(SummitAttendeeExcerptEmail::class, function ($excerpt) {
            $this->assertEquals('outcome@example.com', $this->jobProperty($excerpt, 'to_email'));

            $lines = $this->jobProperty($excerpt, 'payload')[IMailTemplatesConstants::report];
            $errorLines = array_values(array_filter($lines, fn($l) => str_starts_with($l, 'ERROR')));

            $this->assertCount(1, $errorLines, 'the excerpt must carry exactly one ERROR line for the lost chunk');
            $this->assertStringContainsString('11, 22, 33', $errorLines[0], 'the ERROR line must name every attendee id in the chunk');
            $this->assertStringContainsString('worker killed mid-chunk', $errorLines[0], 'the ERROR line must carry the failure reason');
            $this->assertEmpty(
                array_filter($lines, fn($l) => str_starts_with($l, 'Email type')),
                'a failed chunk must not report any e-mail as sent'
            );
            return true;
        });

        Log::shouldHaveReceived('error')
            ->withArgs(fn($message) => is_string($message)
                && str_contains($message, (string) self::$summit->getId())
                && str_contains($message, '11, 22, 33'))
            ->once();
    }

    public function testFailedChunkExcerptFailsOverToTheDatabaseQueueWhenThePrimaryDispatchFails(): void
    {
        // A chunk lands on the database fallback worker precisely when the redis primary was down
        // at dispatch time. If that chunk then fails while redis is still down, a bare ::dispatch()
        // of the excerpt throws, the best-effort catch swallows it, and the operator report is lost
        // in the one scenario it exists for. The excerpt must take the same failover route as the
        // chunk itself (JobDispatcher::withDbFallback): primary throws, database gets the job.
        Log::spy();
        $captured = [];
        $excerpt = Mockery::type(SummitAttendeeExcerptEmail::class);
        // Only the excerpt dispatches are scripted (primary throws, fallback captures); every
        // other dispatch goes to the real dispatcher.
        $realBus = Bus::getFacadeRoot();
        Bus::shouldReceive('dispatch')->with($excerpt)->once()->andThrow(new \RuntimeException('redis down'));
        Bus::shouldReceive('dispatch')->with($excerpt)->once()->andReturnUsing(function ($job) use (&$captured) {
            $captured[] = $job;
            return null;
        });
        Bus::shouldReceive('dispatch')->andReturnUsing(fn($job) => $realBus->dispatch($job));

        $job = new ProcessAttendeesEmailRequestJob(self::$summit, [
            'email_flow_event' => 'SUMMIT_REGISTRATION_GENERIC_ATTENDEE_EMAIL',
            'attendees_ids' => [11, 22],
            'outcome_email_recipient' => 'outcome@example.com',
        ], null);

        $job->failed(new \RuntimeException('worker killed mid-chunk'));

        $this->assertCount(1, $captured, 'after the primary dispatch fails the excerpt must be re-dispatched on the fallback connection, not swallowed');
        $this->assertInstanceOf(SummitAttendeeExcerptEmail::class, $captured[0]);
        $this->assertSame('database', $captured[0]->connection, 'the retry must target the database fallback connection');
        $this->assertEquals('outcome@example.com', $this->jobProperty($captured[0], 'to_email'));

        $lines = $this->jobProperty($captured[0], 'payload')[IMailTemplatesConstants::report];
        $errorLines = array_values(array_filter($lines, fn($l) => str_starts_with($l, 'ERROR')));
        $this->assertCount(1, $errorLines);
        $this->assertStringContainsString('11, 22', $errorLines[0], 'the failed-over excerpt must still name every attendee id in the chunk');
    }

    public function testFailedChunkWithoutOutcomeRecipientOnlyLogsTheUnprocessedIds(): void
    {
        Queue::fake();
        Log::spy();

        $job = new ProcessAttendeesEmailRequestJob(self::$summit, [
            'email_flow_event' => 'SUMMIT_REGISTRATION_GENERIC_ATTENDEE_EMAIL',
            'attendees_ids' => [44, 55],
        ], null);

        $job->failed(new \RuntimeException('worker killed mid-chunk'));

        Queue::assertNotPushed(SummitAttendeeExcerptEmail::class);
        Log::shouldHaveReceived('error')
            ->withArgs(fn($message) => is_string($message) && str_contains($message, '44, 55'))
            ->once();
    }

    public function testFailedChunkLogsFilterFieldNamesButNotTheirValues(): void
    {
        Queue::fake();
        Log::spy();

        $job = new ProcessAttendeesEmailRequestJob(self::$summit, [
            'email_flow_event' => 'SUMMIT_REGISTRATION_GENERIC_ATTENDEE_EMAIL',
            'attendees_ids' => [1],
        ], ['email==someone-private@example.com', 'first_name==Jane']);

        $job->failed(new \RuntimeException('boom'));

        Log::shouldHaveReceived('error')
            ->withArgs(function ($message) {
                if (!is_string($message)) return false;
                $this->assertStringNotContainsString('someone-private@example.com', $message, 'filter VALUES must never reach the log');
                $this->assertStringNotContainsString('Jane', $message);
                return str_contains($message, 'email') && str_contains($message, 'first_name');
            })
            ->once();
    }
}
