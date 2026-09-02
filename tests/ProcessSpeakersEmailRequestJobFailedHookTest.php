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
use App\Jobs\Emails\PresentationSubmissions\SelectionProcess\PresentationSpeakerSelectionProcessExcerptEmail;
use App\Jobs\Emails\ProcessSpeakersEmailRequestJob;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use ReflectionObject;

/**
 * Covers ProcessSpeakersEmailRequestJob::failed(), the hook the queue worker invokes once a
 * chunk job is marked failed (tries = 1, so a chunk whose worker died mid-run is failed without
 * re-running when it is re-served after retry_after). The outcome excerpt is otherwise only sent
 * when sendEmails() runs to completion, so this hook is the only thing that tells the operator
 * which speaker ids never got their e-mail.
 *
 * The hook is invoked directly here: Queue::fake() intercepts dispatch before handle() runs, so
 * the framework's fail() -> failed() plumbing can't be driven end to end without a real queue
 * connection. That plumbing is Laravel's (Illuminate\Queue\Jobs\Job::fail() ->
 * CallQueuedHandler::failed() -> $command->failed($e)); what this class pins is what OUR hook does.
 *
 * Class ProcessSpeakersEmailRequestJobFailedHookTest
 */
class ProcessSpeakersEmailRequestJobFailedHookTest extends ProtectedApiTestCase
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

        $job = new ProcessSpeakersEmailRequestJob(self::$summit->getId(), [
            'email_flow_event' => 'SUMMIT_SUBMISSIONS_PRESENTATION_SPEAKER_ACCEPTED_ALTERNATE',
            'speaker_ids' => [11, 22, 33],
            'outcome_email_recipient' => 'outcome@example.com',
        ], null);

        $job->failed(new \RuntimeException('worker killed mid-chunk'));

        Queue::assertPushed(PresentationSpeakerSelectionProcessExcerptEmail::class, 1);
        Queue::assertPushed(PresentationSpeakerSelectionProcessExcerptEmail::class, function ($excerpt) {
            $this->assertEquals('outcome@example.com', $this->jobProperty($excerpt, 'to_email'));

            $lines = $this->jobProperty($excerpt, 'payload')[IMailTemplatesConstants::report];
            $errorLines = array_values(array_filter($lines, fn($l) => str_starts_with($l, 'ERROR')));

            $this->assertCount(1, $errorLines, 'the excerpt must carry exactly one ERROR line for the lost chunk');
            $this->assertStringContainsString('11, 22, 33', $errorLines[0], 'the ERROR line must name every speaker id in the chunk');
            $this->assertStringContainsString('worker killed mid-chunk', $errorLines[0], 'the ERROR line must carry the failure reason');
            // The chunk runs one speaker per transaction, so a mid-run kill has already e-mailed
            // part of it: the line must not present the ids as confirmed misses, and it must tell
            // the operator how to re-send without duplicating those.
            $this->assertStringContainsString('up to 3 of them may not have been processed', $errorLines[0]);
            $this->assertStringContainsString('should_resend=false', $errorLines[0], 'the ERROR line must tell the operator to re-send with should_resend=false');
            $this->assertStringNotContainsString('promo code', $errorLines[0], 'no promo-code caveat when the send carries no promo_code_spec');
            $this->assertEmpty(
                array_filter($lines, fn($l) => str_starts_with($l, 'Email type')),
                'a failed chunk must not report any e-mail as sent'
            );
            return true;
        });

        Log::shouldHaveReceived('error')
            ->withArgs(fn($message) => is_string($message)
                && str_contains($message, (string) self::$summit->getId())
                && str_contains($message, '11, 22, 33')
                && str_contains($message, 'should_resend=false'))
            ->once();
    }

    public function testFailedChunkWithPromoCodeSpecWarnsThatAResendCreatesNewCodes(): void
    {
        // AutomaticMultiSpeakerPromoCodeStrategy::getPromoCode() generates a fresh code on every
        // call and runs before the resend guard, so should_resend=false does not stop a re-send
        // from handing a second code to the speakers already e-mailed. The operator has to know.
        Queue::fake();
        Log::spy();

        $job = new ProcessSpeakersEmailRequestJob(self::$summit->getId(), [
            'email_flow_event' => 'SUMMIT_SUBMISSIONS_PRESENTATION_SPEAKER_ACCEPTED_ALTERNATE',
            'speaker_ids' => [11, 22],
            'outcome_email_recipient' => 'outcome@example.com',
            'promo_code_spec' => ['class_name' => 'SPEAKERS_PROMO_CODE', 'type' => 'ACCEPTED'],
        ], null);

        $job->failed(new \RuntimeException('worker killed mid-chunk'));

        Queue::assertPushed(PresentationSpeakerSelectionProcessExcerptEmail::class, function ($excerpt) {
            $lines = $this->jobProperty($excerpt, 'payload')[IMailTemplatesConstants::report];
            $errorLines = array_values(array_filter($lines, fn($l) => str_starts_with($l, 'ERROR')));
            $this->assertCount(1, $errorLines);
            $this->assertStringContainsString('should_resend=false', $errorLines[0]);
            $this->assertStringContainsString('auto-generates promo codes', $errorLines[0], 'a promo_code_spec send must warn that a re-send creates new codes');
            return true;
        });
    }

    public function testFailedChunkWithoutOutcomeRecipientOnlyLogsTheUnprocessedIds(): void
    {
        Queue::fake();
        Log::spy();

        $job = new ProcessSpeakersEmailRequestJob(self::$summit->getId(), [
            'email_flow_event' => 'SUMMIT_SUBMISSIONS_PRESENTATION_SPEAKER_ACCEPTED_ALTERNATE',
            'speaker_ids' => [44, 55],
        ], null);

        $job->failed(new \RuntimeException('worker killed mid-chunk'));

        Queue::assertNothingPushed();

        // Exactly one error line, and it names the ids. The "exactly one" half is what
        // distinguishes "skipped the excerpt on purpose" from "tried to build it without a
        // recipient and swallowed the resulting exception into a second error entry".
        Log::shouldHaveReceived('error')->once();
        Log::shouldHaveReceived('error')
            ->withArgs(fn($message) => is_string($message) && str_contains($message, '44, 55'))
            ->once();
    }
}
