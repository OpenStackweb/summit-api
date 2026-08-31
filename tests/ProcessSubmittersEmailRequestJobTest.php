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

use App\Jobs\Emails\ProcessSubmittersEmailRequestJob;
use Mockery;
use services\model\ISubmitterService;
use utils\Filter;

/**
 * Class ProcessSubmittersEmailRequestJobTest
 *
 * Tests that the job's internal FilterParser::parse allow-list mirrors the
 * controller's send() allow-list. The queue driver in the test environment is
 * redis, so dispatched jobs are pushed to the queue but never executed during
 * the test run — no worker runs during the suite. Controller-level send tests
 * therefore cannot detect a missing field in the job's allow-list; only direct
 * handle() invocation reveals the gap.
 */
final class ProcessSubmittersEmailRequestJobTest extends TestCase
{
    public function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function makePayload(): array
    {
        return ['email_flow_event' => 'SUMMIT_SUBMISSIONS_PRESENTATION_SUBMITTER_ACCEPTED_ONLY'];
    }

    private function captureFilter(Mockery\MockInterface $service): \stdClass
    {
        $captured = new \stdClass();
        $captured->filter = null;
        $service->shouldReceive('sendEmails')
            ->once()
            ->andReturnUsing(function (int $id, array $payload, ?Filter $filter) use ($captured) {
                $captured->filter = $filter;
            });
        return $captured;
    }

    public function testHandleAcceptsHasPublishedPresentationsTrueFilter(): void
    {
        $service  = Mockery::mock(ISubmitterService::class);
        $captured = $this->captureFilter($service);

        $job = new ProcessSubmittersEmailRequestJob(
            999,
            $this->makePayload(),
            ['has_published_presentations==true']
        );

        $job->handle($service);

        $this->assertInstanceOf(Filter::class, $captured->filter,
            'handle() must pass a parsed Filter to sendEmails, not throw FilterParserException');
    }

    public function testHandleAcceptsHasPublishedPresentationsFalseFilter(): void
    {
        $service  = Mockery::mock(ISubmitterService::class);
        $captured = $this->captureFilter($service);

        $job = new ProcessSubmittersEmailRequestJob(
            999,
            $this->makePayload(),
            ['has_published_presentations==false']
        );

        $job->handle($service);

        $this->assertInstanceOf(Filter::class, $captured->filter);
    }

    public function testHandleAcceptsHasPublishedPresentationsCombinedWithOtherFilters(): void
    {
        // Verify the field works alongside the sibling filters already in the job allow-list.
        $service  = Mockery::mock(ISubmitterService::class);
        $captured = $this->captureFilter($service);

        $job = new ProcessSubmittersEmailRequestJob(
            999,
            $this->makePayload(),
            ['has_published_presentations==true', 'has_accepted_presentations==true']
        );

        $job->handle($service);

        $this->assertInstanceOf(Filter::class, $captured->filter);
    }
}
