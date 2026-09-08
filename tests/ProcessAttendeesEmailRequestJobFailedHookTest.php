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
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
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
