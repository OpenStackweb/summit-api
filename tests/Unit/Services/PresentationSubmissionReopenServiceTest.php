<?php namespace Tests\Unit\Services;
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

use App\Models\Foundation\Summit\SelectionPlan;
use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Support\Facades\Facade;
use libs\utils\ITransactionService;
use Mockery;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use models\main\Member;
use models\summit\Presentation;
use models\summit\Summit;
use models\summit\SummitEvent;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Log\LoggerInterface;
use services\model\PresentationSubmissionReopenService;

/**
 * Class PresentationSubmissionReopenServiceTest
 *
 * Unit tests for every validation branch of {@see PresentationSubmissionReopenService::reopen}
 * and {@see PresentationSubmissionReopenService::closeNow}.
 *
 * Two collaborators have to be stood up for this to be a real unit test:
 *
 *  - the Config facade (the service resolves cfp.default_reopen_hours / cfp.max_reopen_hours).
 *    A bare Illuminate container with a real Config\Repository bound to 'config' is wired as
 *    the facade root, mirroring CompanyFileProcessingTest -- no Laravel app, no .env, and the
 *    configured values are set per test so the guards are proven to read config rather than
 *    their hardcoded fallbacks.
 *  - ITransactionService, mocked so it actually invokes the closure and returns its value.
 *    Every test asserts the callback ran exactly once; a mock that swallowed the closure would
 *    make all of these pass vacuously.
 *
 * The hours < 1 branch is unreachable over HTTP -- the endpoint validates
 * 'hours' => 'sometimes|integer|min:1' and refuses first -- so this file is the only coverage
 * it will ever have.
 *
 * @package Tests\Unit\Services
 */
class PresentationSubmissionReopenServiceTest extends TestCase
{
    private Container $app;

    private Repository $config;

    /**
     * Spy bound as the Log facade root; see setUp().
     */
    private $log;

    /**
     * Number of times the mocked transaction closure actually executed.
     */
    private int $tx_invocations = 0;

    protected function setUp(): void
    {
        parent::setUp();
        Facade::clearResolvedInstances();
        $this->app = new Container();
        $this->config = new Repository([
            'cfp' => [
                'max_reopen_hours' => 48,
                'default_reopen_hours' => 5,
            ],
        ]);
        $this->app->instance('config', $this->config);

        // closeNow() logs the revocation before clearing the grant, so 'log' has to be bound or
        // the Log facade has no root. The spy doubles as the assertion surface for that line.
        $this->log = Mockery::spy(LoggerInterface::class);
        $this->app->instance('log', $this->log);

        Container::setInstance($this->app);
        Facade::setFacadeApplication($this->app);
        $this->tx_invocations = 0;
    }

    protected function tearDown(): void
    {
        Facade::setFacadeApplication(null);
        Facade::clearResolvedInstances();
        Container::setInstance(null);
        Mockery::close();
        parent::tearDown();
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function utc(string $modifier): \DateTime
    {
        return new \DateTime($modifier, new \DateTimeZone('UTC'));
    }

    /**
     * Transaction service that really runs the closure, so the code under test executes.
     */
    private function makeService(): PresentationSubmissionReopenService
    {
        $tx_service = Mockery::mock(ITransactionService::class);
        $tx_service->shouldReceive('transaction')
            ->once()
            ->andReturnUsing(function (\Closure $callback) {
                $this->tx_invocations++;
                return $callback();
            });

        return new PresentationSubmissionReopenService($tx_service);
    }

    private function plan(?\DateTime $submission_end_date, bool $enabled = true): SelectionPlan
    {
        $plan = Mockery::mock(SelectionPlan::class);
        $plan->shouldReceive('IsEnabled')->andReturn($enabled);
        $plan->shouldReceive('getSubmissionEndDate')->andReturn($submission_end_date);
        $plan->shouldReceive('getId')->andReturn(1);
        return $plan;
    }

    private function member(int $id = 42): Member
    {
        $member = Mockery::mock(Member::class);
        $member->shouldReceive('getId')->andReturn($id);
        $member->shouldReceive('getFullName')->andReturn('Jane Doe');
        $member->shouldReceive('getEmail')->andReturn('jane@example.com');
        return $member;
    }

    private function presentation(?SelectionPlan $plan = null): Presentation
    {
        $presentation = new Presentation();
        if (!is_null($plan)) $presentation->setSelectionPlan($plan);
        return $presentation;
    }

    /**
     * @param Presentation|SummitEvent|null $event what getEvent() hands back
     */
    private function summit($event): Summit
    {
        $summit = Mockery::mock(Summit::class);
        $summit->shouldReceive('getEvent')->with(1234)->andReturn($event);
        $summit->shouldReceive('getId')->andReturn(99);
        return $summit;
    }

    private function assertClosureRan(): void
    {
        $this->assertSame(1, $this->tx_invocations, 'the transaction closure never executed');
    }

    // -------------------------------------------------------------------------
    // reopen(): entity resolution
    // -------------------------------------------------------------------------

    public function testReopenThrowsEntityNotFoundWhenTheEventDoesNotExistInTheSummit(): void
    {
        $service = $this->makeService();

        $this->expectException(EntityNotFoundException::class);
        $this->expectExceptionMessage('Presentation 1234 not found.');

        try {
            $service->reopen($this->summit(null), 1234, 24, $this->member());
        } finally {
            $this->assertClosureRan();
        }
    }

    public function testReopenThrowsEntityNotFoundWhenTheEventIsNotAPresentation(): void
    {
        $service = $this->makeService();
        $event = Mockery::mock(SummitEvent::class);

        $this->expectException(EntityNotFoundException::class);
        $this->expectExceptionMessage('Presentation 1234 not found.');

        try {
            $service->reopen($this->summit($event), 1234, 24, $this->member());
        } finally {
            $this->assertClosureRan();
        }
    }

    // -------------------------------------------------------------------------
    // reopen(): the hours guard
    // -------------------------------------------------------------------------

    public function testReopenWithNullHoursResolvesTheConfiguredDefaultAndStampsIt(): void
    {
        $service = $this->makeService();
        $presentation = $this->presentation($this->plan($this->utc('-1 hour')));
        $actor = $this->member();

        $result = $service->reopen($this->summit($presentation), 1234, null, $actor);

        // 5, not the hardcoded 24 fallback -- proof the default comes from config
        $this->assertSame(5, $result->getSubmissionReopenedHours());
        $this->assertClosureRan();
    }

    /**
     * A deployment that sets default_reopen_hours above max_reopen_hours would otherwise reject
     * every request that omits hours, which is a config error the caller can neither see nor fix.
     * The resolved default is clamped to the ceiling; an explicit out-of-range hours is still
     * refused (see testReopenAboveTheConfiguredMaxThrowsValidation).
     */
    public function testMisconfiguredDefaultAboveMaxIsClampedRatherThanRefused(): void
    {
        $this->config->set('cfp.default_reopen_hours', 500);
        $this->config->set('cfp.max_reopen_hours', 48);

        $service = $this->makeService();
        $presentation = $this->presentation($this->plan($this->utc('-1 hour')));

        $result = $service->reopen($this->summit($presentation), 1234, null, $this->member());

        $this->assertSame(48, $result->getSubmissionReopenedHours());
        $this->assertClosureRan();
    }

    public function testReopenAboveTheConfiguredMaxThrowsValidation(): void
    {
        $service = $this->makeService();
        $presentation = $this->presentation($this->plan($this->utc('-1 hour')));

        $this->expectException(ValidationException::class);
        // 48 is the configured ceiling, not the hardcoded 168 fallback
        $this->expectExceptionMessage('hours must be between 1 and 48.');

        try {
            $service->reopen($this->summit($presentation), 1234, 49, $this->member());
        } finally {
            $this->assertClosureRan();
            // nothing stamped
            $this->assertNull($presentation->getSubmissionReopenedHours());
            $this->assertNull($presentation->getSubmissionReopenedDate());
        }
    }

    public function testReopenAtExactlyTheConfiguredMaxIsAccepted(): void
    {
        $service = $this->makeService();
        $presentation = $this->presentation($this->plan($this->utc('-1 hour')));

        $result = $service->reopen($this->summit($presentation), 1234, 48, $this->member());

        $this->assertSame(48, $result->getSubmissionReopenedHours());
        $this->assertClosureRan();
    }

    /**
     * The lower bound. Unreachable over HTTP (the endpoint validates min:1 first), so these are
     * the only assertions that will ever cover it.
     */
    #[DataProvider('belowMinimumHoursProvider')]
    public function testReopenBelowOneHourThrowsValidation(int $hours): void
    {
        $service = $this->makeService();
        $presentation = $this->presentation($this->plan($this->utc('-1 hour')));

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('hours must be between 1 and 48.');

        try {
            $service->reopen($this->summit($presentation), 1234, $hours, $this->member());
        } finally {
            $this->assertClosureRan();
            $this->assertNull($presentation->getSubmissionReopenedHours());
        }
    }

    public static function belowMinimumHoursProvider(): array
    {
        return [
            'zero' => [0],
            'negative' => [-1],
            // a negative interval would make getSubmissionReopenedUntil() throw on every read
            'large negative' => [-168],
        ];
    }

    public function testReopenAtExactlyOneHourIsAccepted(): void
    {
        $service = $this->makeService();
        $presentation = $this->presentation($this->plan($this->utc('-1 hour')));

        $result = $service->reopen($this->summit($presentation), 1234, 1, $this->member());

        $this->assertSame(1, $result->getSubmissionReopenedHours());
        $this->assertClosureRan();
    }

    // -------------------------------------------------------------------------
    // reopen(): plan-state guards
    // -------------------------------------------------------------------------

    public function testReopenWithNoSelectionPlanAssignedThrowsValidation(): void
    {
        $service = $this->makeService();
        $presentation = $this->presentation();

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Presentation is not assigned to any selection plan.');

        try {
            $service->reopen($this->summit($presentation), 1234, 24, $this->member());
        } finally {
            $this->assertClosureRan();
            $this->assertNull($presentation->getSubmissionReopenedHours());
        }
    }

    public function testReopenOnADisabledPlanThrowsValidation(): void
    {
        $service = $this->makeService();
        $presentation = $this->presentation($this->plan($this->utc('-1 hour'), false));

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Selection plan is not enabled.');

        try {
            $service->reopen($this->summit($presentation), 1234, 24, $this->member());
        } finally {
            $this->assertClosureRan();
            $this->assertNull($presentation->getSubmissionReopenedHours());
        }
    }

    public function testReopenOnAPlanWithNoSubmissionEndDateThrowsValidation(): void
    {
        $service = $this->makeService();
        $presentation = $this->presentation($this->plan(null));

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Selection plan has no submission end date.');

        try {
            $service->reopen($this->summit($presentation), 1234, 24, $this->member());
        } finally {
            $this->assertClosureRan();
            $this->assertNull($presentation->getSubmissionReopenedHours());
        }
    }

    public function testReopenWhenTheSubmissionWindowHasNotEndedThrowsValidation(): void
    {
        $service = $this->makeService();
        $presentation = $this->presentation($this->plan($this->utc('+1 hour')));

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Submission period has not ended yet; nothing to reopen.');

        try {
            $service->reopen($this->summit($presentation), 1234, 24, $this->member());
        } finally {
            $this->assertClosureRan();
            $this->assertNull($presentation->getSubmissionReopenedHours());
        }
    }

    // -------------------------------------------------------------------------
    // reopen(): happy path
    // -------------------------------------------------------------------------

    public function testReopenStampsHoursDateAndActorAndReturnsThePresentation(): void
    {
        $service = $this->makeService();
        $presentation = $this->presentation($this->plan($this->utc('-1 hour')));
        $actor = $this->member(77);
        $before = $this->utc('now');

        $result = $service->reopen($this->summit($presentation), 1234, 12, $actor);

        $this->assertSame($presentation, $result);
        $this->assertSame(12, $result->getSubmissionReopenedHours());
        $this->assertSame($actor, $result->getSubmissionReopenedBy());
        $this->assertSame(77, $result->getSubmissionReopenedById());
        $this->assertGreaterThanOrEqual($before, $result->getSubmissionReopenedDate());
        $this->assertEquals(
            (clone $result->getSubmissionReopenedDate())->add(new \DateInterval('PT12H')),
            $result->getSubmissionReopenedUntil()
        );
        $this->assertTrue($result->isSubmissionReopened());
        $this->assertClosureRan();
    }

    // -------------------------------------------------------------------------
    // closeNow(): clears the grant, and deliberately applies no plan-state validation
    // -------------------------------------------------------------------------

    public function testCloseNowClearsTheGrant(): void
    {
        $service = $this->makeService();
        $presentation = $this->presentation($this->plan($this->utc('-1 hour')));
        $presentation->reopenSubmission(24, $this->member());

        $service->closeNow($this->summit($presentation), 1234, $this->member());

        $this->assertNull($presentation->getSubmissionReopenedHours());
        $this->assertNull($presentation->getSubmissionReopenedDate());
        $this->assertNull($presentation->getSubmissionReopenedBy());
        $this->assertClosureRan();
    }

    /**
     * The revoking actor is only observable through this log line, since closeSubmissionNow()
     * nulls the actor column. It is also the only thing that catches $actor being dropped from
     * the transaction closure's use list, which is how it was inert to begin with.
     */
    public function testCloseNowLogsTheRevocationWithBothActors(): void
    {
        $service = $this->makeService();
        $presentation = $this->presentation($this->plan($this->utc('-1 hour')));
        $presentation->reopenSubmission(24, $this->member(7));

        $service->closeNow($this->summit($presentation), 1234, $this->member(11));

        $this->log->shouldHaveReceived('info')->once()->withArgs(function (string $message) {
            return str_contains($message, 'summit 99')
                && str_contains($message, 'presentation 1234')
                && str_contains($message, 'revoked by member 11')
                && str_contains($message, 'granted by member 7');
        });
        $this->assertClosureRan();
    }

    public function testCloseNowSucceedsOnADisabledPlan(): void
    {
        $service = $this->makeService();
        $presentation = $this->presentation($this->plan($this->utc('-1 hour'), false));
        $presentation->reopenSubmission(24, $this->member());

        $service->closeNow($this->summit($presentation), 1234, $this->member());

        $this->assertNull($presentation->getSubmissionReopenedHours());
        $this->assertClosureRan();
    }

    public function testCloseNowSucceedsWithNoSelectionPlanAssigned(): void
    {
        $service = $this->makeService();
        $presentation = $this->presentation();
        $presentation->reopenSubmission(24, $this->member());

        $service->closeNow($this->summit($presentation), 1234, $this->member());

        $this->assertNull($presentation->getSubmissionReopenedHours());
        $this->assertClosureRan();
    }

    public function testCloseNowSucceedsWhileTheSubmissionWindowIsStillOpen(): void
    {
        $service = $this->makeService();
        $presentation = $this->presentation($this->plan($this->utc('+1 hour')));
        $presentation->reopenSubmission(24, $this->member());

        $service->closeNow($this->summit($presentation), 1234, $this->member());

        $this->assertNull($presentation->getSubmissionReopenedHours());
        $this->assertClosureRan();
    }

    public function testCloseNowIsIdempotentWhenThereIsNoGrant(): void
    {
        $service = $this->makeService();
        $presentation = $this->presentation($this->plan($this->utc('-1 hour')));

        $service->closeNow($this->summit($presentation), 1234, $this->member());

        $this->assertNull($presentation->getSubmissionReopenedHours());
        $this->assertClosureRan();
    }

    public function testCloseNowThrowsEntityNotFoundWhenTheEventDoesNotExistInTheSummit(): void
    {
        $service = $this->makeService();

        $this->expectException(EntityNotFoundException::class);
        $this->expectExceptionMessage('Presentation 1234 not found.');

        try {
            $service->closeNow($this->summit(null), 1234, $this->member());
        } finally {
            $this->assertClosureRan();
        }
    }

    public function testCloseNowThrowsEntityNotFoundWhenTheEventIsNotAPresentation(): void
    {
        $service = $this->makeService();
        $event = Mockery::mock(SummitEvent::class);

        $this->expectException(EntityNotFoundException::class);
        $this->expectExceptionMessage('Presentation 1234 not found.');

        try {
            $service->closeNow($this->summit($event), 1234, $this->member());
        } finally {
            $this->assertClosureRan();
        }
    }

    // -------------------------------------------------------------------------
    // Guard on the harness itself
    // -------------------------------------------------------------------------

    public function testTheMockedTransactionServiceReallyExecutesTheClosure(): void
    {
        // Without this, a transaction mock that returned a canned value would let every test
        // above pass without running a single line of the service.
        $service = $this->makeService();
        $presentation = $this->presentation($this->plan($this->utc('-1 hour')));

        $this->assertSame(0, $this->tx_invocations);
        $service->reopen($this->summit($presentation), 1234, 3, $this->member());
        $this->assertSame(1, $this->tx_invocations);
        $this->assertSame(3, $presentation->getSubmissionReopenedHours());
    }
}
