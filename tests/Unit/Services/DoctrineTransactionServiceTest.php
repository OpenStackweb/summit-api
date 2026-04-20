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

use Closure;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\RetryableException;
use Doctrine\DBAL\TransactionIsolationLevel;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Illuminate\Support\Facades\Facade;
use LaravelDoctrine\ORM\Facades\Registry;
use Mockery;
use PHPUnit\Framework\TestCase;
use services\utils\DoctrineTransactionService;

/**
 * A concrete retryable exception for testing.
 * ConnectionLost requires a Driver\Exception argument which is hard to construct;
 * RetryableException is a marker interface that triggers the same retry logic.
 */
class TestRetryableException extends \RuntimeException implements RetryableException
{
}

/**
 * Unit tests for DoctrineTransactionService root-vs-nested transaction behavior.
 *
 * Covers:
 * - Root transaction: retry on connection errors, flush, commit, EM reset
 * - Nested transaction: no retry, no EM reset, no connection close, flush, exception propagation
 * - Direct nesting: inner transaction() called inside outer transaction() (e.g. email-send pattern)
 * - Indirect nesting: service method with its own transaction() called from within another transaction()
 * - Connection error in nested: no destructive recovery, exception propagates to root
 */
class DoctrineTransactionServiceTest extends TestCase
{
    private $container;

    protected function setUp(): void
    {
        parent::setUp();
        Facade::clearResolvedInstances();
        $this->container = new \Illuminate\Container\Container();
        $this->container->instance('app', $this->container);
        $this->container->instance('log', new class {
            public function __call($name, $args) { /* swallow */ }
        });
        Facade::setFacadeApplication($this->container);
    }

    protected function tearDown(): void
    {
        Facade::clearResolvedInstances();
        Facade::setFacadeApplication(null);
        Mockery::close();
        parent::tearDown();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Build mocked EM + Connection and register them in the facade.
     *
     * @param bool $transactionActive  Initial state of isTransactionActive()
     * @return array{EntityManagerInterface&\Mockery\MockInterface, Connection&\Mockery\MockInterface}
     */
    private function buildMocks(bool $transactionActive = false): array
    {
        $conn = Mockery::mock(Connection::class);
        $conn->shouldReceive('isTransactionActive')->andReturn($transactionActive)->byDefault();
        $conn->shouldReceive('setNestTransactionsWithSavepoints')->byDefault();
        $conn->shouldReceive('setTransactionIsolation')->byDefault();
        $conn->shouldReceive('beginTransaction')->byDefault();
        $conn->shouldReceive('commit')->byDefault();
        $conn->shouldReceive('rollBack')->byDefault();
        $conn->shouldReceive('close')->byDefault();

        $em = Mockery::mock(EntityManagerInterface::class);
        $em->shouldReceive('getConnection')->andReturn($conn)->byDefault();
        $em->shouldReceive('isOpen')->andReturn(true)->byDefault();
        $em->shouldReceive('flush')->byDefault();
        $em->shouldReceive('clear')->byDefault();
        $em->shouldReceive('close')->byDefault();

        $registry = Mockery::mock(ManagerRegistry::class);
        $registry->shouldReceive('getManager')->with('default')->andReturn($em)->byDefault();
        $registry->shouldReceive('resetManager')->with('default')->andReturn($em)->byDefault();

        $this->container->instance(ManagerRegistry::class, $registry);

        return [$em, $conn, $registry];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // ROOT TRANSACTION TESTS
    // ─────────────────────────────────────────────────────────────────────────

    public function testRootTransactionCommitsAndFlushes(): void
    {
        [$em, $conn] = $this->buildMocks(transactionActive: false);

        $conn->shouldReceive('setNestTransactionsWithSavepoints')->byDefault();
        $conn->shouldReceive('setTransactionIsolation')
            ->once()
            ->with(TransactionIsolationLevel::READ_COMMITTED);
        $conn->shouldReceive('beginTransaction')->once();
        $em->shouldReceive('flush')->once();
        $conn->shouldReceive('commit')->once();

        $service = new DoctrineTransactionService('default');
        $result = $service->transaction(function () {
            return 'success';
        });

        $this->assertSame('success', $result);
    }

    public function testRootTransactionRollsBackOnNonRetryableException(): void
    {
        [$em, $conn] = $this->buildMocks(transactionActive: false);

        $conn->shouldReceive('beginTransaction')->once();
        $conn->shouldReceive('isTransactionActive')->andReturn(true);
        $conn->shouldReceive('rollBack')->once();
        $conn->shouldReceive('commit')->never();
        $em->shouldReceive('flush')->never();

        $service = new DoctrineTransactionService('default');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('business error');

        $service->transaction(function () {
            throw new \RuntimeException('business error');
        });
    }

    public function testRootTransactionRetriesOnConnectionLost(): void
    {
        $conn = Mockery::mock(Connection::class);
        $conn->shouldReceive('setNestTransactionsWithSavepoints')->byDefault();
        $conn->shouldReceive('setTransactionIsolation')->byDefault();
        $conn->shouldReceive('beginTransaction')->byDefault();
        $conn->shouldReceive('commit')->byDefault();
        $conn->shouldReceive('rollBack')->byDefault();
        $conn->shouldReceive('close')->byDefault();

        // First call: isTransactionActive returns false (root detection)
        // then returns true inside the inner catch for rollback check
        $txActiveSequence = [false, true, false];
        $txActiveIndex = 0;
        $conn->shouldReceive('isTransactionActive')->andReturnUsing(
            function () use (&$txActiveSequence, &$txActiveIndex) {
                $val = $txActiveSequence[$txActiveIndex] ?? false;
                $txActiveIndex++;
                return $val;
            }
        );

        $em = Mockery::mock(EntityManagerInterface::class);
        $em->shouldReceive('getConnection')->andReturn($conn)->byDefault();
        $em->shouldReceive('isOpen')->andReturn(true)->byDefault();
        $em->shouldReceive('flush')->byDefault();
        $em->shouldReceive('clear')->byDefault();
        $em->shouldReceive('close')->byDefault();

        $registry = Mockery::mock(ManagerRegistry::class);
        $registry->shouldReceive('getManager')->with('default')->andReturn($em)->byDefault();
        $registry->shouldReceive('resetManager')->with('default')->andReturn($em);

        $this->container->instance(ManagerRegistry::class, $registry);

        $callCount = 0;
        $service = new DoctrineTransactionService('default');
        $result = $service->transaction(function () use (&$callCount) {
            $callCount++;
            if ($callCount === 1) {
                throw new TestRetryableException('Connection lost');
            }
            return 'recovered';
        });

        $this->assertSame('recovered', $result);
        $this->assertSame(2, $callCount, 'Callback should be retried after connection lost');
    }

    public function testRootTransactionResetsManagerOnConnectionError(): void
    {
        $conn = Mockery::mock(Connection::class);
        $conn->shouldReceive('isTransactionActive')->andReturn(false, true, false);
        $conn->shouldReceive('setNestTransactionsWithSavepoints')->byDefault();
        $conn->shouldReceive('setTransactionIsolation')->byDefault();
        $conn->shouldReceive('beginTransaction')->byDefault();
        $conn->shouldReceive('commit')->byDefault();
        $conn->shouldReceive('rollBack')->byDefault();
        $conn->shouldReceive('close')->byDefault();

        $em = Mockery::mock(EntityManagerInterface::class);
        $em->shouldReceive('getConnection')->andReturn($conn)->byDefault();
        $em->shouldReceive('isOpen')->andReturn(true)->byDefault();
        $em->shouldReceive('flush')->byDefault();
        $em->shouldReceive('clear')->once(); // destructive recovery
        $em->shouldReceive('close')->once(); // destructive recovery

        $registry = Mockery::mock(ManagerRegistry::class);
        $registry->shouldReceive('getManager')->with('default')->andReturn($em)->byDefault();
        $registry->shouldReceive('resetManager')->with('default')->once()->andReturn($em);

        $this->container->instance(ManagerRegistry::class, $registry);

        $callCount = 0;
        $service = new DoctrineTransactionService('default');
        $service->transaction(function () use (&$callCount) {
            $callCount++;
            if ($callCount === 1) {
                throw new TestRetryableException('gone');
            }
            return 'ok';
        });

        $this->assertSame(2, $callCount);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // NESTED TRANSACTION TESTS
    // ─────────────────────────────────────────────────────────────────────────

    public function testNestedTransactionFlushesAndCommits(): void
    {
        // Connection already has an active transaction → detected as nested
        [$em, $conn] = $this->buildMocks(transactionActive: true);

        $conn->shouldReceive('beginTransaction')->once();
        $em->shouldReceive('flush')->once();
        $conn->shouldReceive('commit')->once();
        // Must NOT set isolation level in nested mode
        $conn->shouldReceive('setTransactionIsolation')->never();

        $service = new DoctrineTransactionService('default');
        $result = $service->transaction(function () {
            return 'nested-result';
        });

        $this->assertSame('nested-result', $result);
    }

    public function testNestedTransactionDoesNotRetryOnConnectionError(): void
    {
        [$em, $conn] = $this->buildMocks(transactionActive: true);

        $conn->shouldReceive('isTransactionActive')->andReturn(true);
        $conn->shouldReceive('rollBack')->once();

        // Must NOT reset manager or close connection in nested
        $registry = $this->container->make(ManagerRegistry::class);
        $registry->shouldReceive('resetManager')->never();
        $conn->shouldReceive('close')->never();
        $em->shouldReceive('close')->never();
        $em->shouldReceive('clear')->never();

        $callCount = 0;
        $service = new DoctrineTransactionService('default');

        try {
            $service->transaction(function () use (&$callCount) {
                $callCount++;
                throw new TestRetryableException('inner connection lost');
            });
            $this->fail('Expected exception was not thrown');
        } catch (TestRetryableException $e) {
            // expected
        }

        $this->assertSame(1, $callCount, 'Nested transaction must NOT retry');
    }

    public function testNestedTransactionRollsBackAndRethrowsOnError(): void
    {
        [$em, $conn] = $this->buildMocks(transactionActive: true);

        $conn->shouldReceive('isTransactionActive')->andReturn(true);
        $conn->shouldReceive('rollBack')->once();
        $conn->shouldReceive('commit')->never();
        $em->shouldReceive('flush')->never(); // flush not reached on error

        $service = new DoctrineTransactionService('default');

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('inner failure');

        $service->transaction(function () {
            throw new \LogicException('inner failure');
        });
    }

    // ─────────────────────────────────────────────────────────────────────────
    // DIRECT NESTING: inner transaction() inside outer transaction()
    // Simulates the email-send pattern (SummitPromoCodeService, etc.)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Simulates the pattern at SummitPromoCodeService.php:980-998,
     * SummitRSVPInvitationService.php:448-458, etc.
     *
     * Outer transaction starts (root), inner transaction is called from within
     * the outer's closure. Inner must detect the active transaction and run
     * as nested (no retry, no EM reset).
     */
    public function testDirectNestingInnerRunsAsNested(): void
    {
        $conn = Mockery::mock(Connection::class);
        $conn->shouldReceive('setNestTransactionsWithSavepoints')->byDefault();
        $conn->shouldReceive('setTransactionIsolation')->once(); // only root sets isolation
        $conn->shouldReceive('close')->byDefault();

        // Track transaction active state: false initially (root detection),
        // then true once beginTransaction is called (for nested detection)
        $txActive = false;
        $conn->shouldReceive('isTransactionActive')->andReturnUsing(function () use (&$txActive) {
            return $txActive;
        });
        $conn->shouldReceive('beginTransaction')->andReturnUsing(function () use (&$txActive) {
            $txActive = true;
        });
        $conn->shouldReceive('commit')->andReturnUsing(function () use (&$txActive) {
            // Only outer commit sets txActive to false
            // In real Doctrine this decrements a counter, simulate with simple bool
        });
        $conn->shouldReceive('rollBack')->byDefault();

        $em = Mockery::mock(EntityManagerInterface::class);
        $em->shouldReceive('getConnection')->andReturn($conn)->byDefault();
        $em->shouldReceive('isOpen')->andReturn(true)->byDefault();
        $em->shouldReceive('flush')->byDefault();
        $em->shouldReceive('clear')->byDefault();
        $em->shouldReceive('close')->byDefault();

        $registry = Mockery::mock(ManagerRegistry::class);
        $registry->shouldReceive('getManager')->with('default')->andReturn($em)->byDefault();
        $registry->shouldReceive('resetManager')->with('default')->andReturn($em)->byDefault();

        $this->container->instance(ManagerRegistry::class, $registry);

        $service = new DoctrineTransactionService('default');

        $innerExecuted = false;
        $result = $service->transaction(function ($tx) use ($service, &$innerExecuted) {
            // Simulates the inner transaction (e.g. getByIdExclusiveLock pattern)
            $innerResult = $service->transaction(function () use (&$innerExecuted) {
                $innerExecuted = true;
                return 'promo_code_entity';
            });

            // Outer continues with the result (e.g. dispatches email)
            return "dispatched:{$innerResult}";
        });

        $this->assertTrue($innerExecuted);
        $this->assertSame('dispatched:promo_code_entity', $result);
    }

    /**
     * Direct nesting: inner transaction throws — exception propagates to outer,
     * outer catches and the root handles the full rollback.
     * No EM reset or connection close happens during inner failure.
     */
    public function testDirectNestingInnerErrorPropagatesWithoutDestroyingEM(): void
    {
        $conn = Mockery::mock(Connection::class);
        $conn->shouldReceive('setNestTransactionsWithSavepoints')->byDefault();
        $conn->shouldReceive('setTransactionIsolation')->byDefault();
        $conn->shouldReceive('close')->never(); // inner must NOT close connection

        $txActive = false;
        $conn->shouldReceive('isTransactionActive')->andReturnUsing(function () use (&$txActive) {
            return $txActive;
        });
        $conn->shouldReceive('beginTransaction')->andReturnUsing(function () use (&$txActive) {
            $txActive = true;
        });
        $conn->shouldReceive('commit')->byDefault();
        $conn->shouldReceive('rollBack')->byDefault();

        $em = Mockery::mock(EntityManagerInterface::class);
        $em->shouldReceive('getConnection')->andReturn($conn)->byDefault();
        $em->shouldReceive('isOpen')->andReturn(true)->byDefault();
        $em->shouldReceive('flush')->byDefault();
        $em->shouldReceive('clear')->never(); // inner must NOT clear EM
        $em->shouldReceive('close')->never(); // inner must NOT close EM

        $registry = Mockery::mock(ManagerRegistry::class);
        $registry->shouldReceive('getManager')->with('default')->andReturn($em)->byDefault();
        $registry->shouldReceive('resetManager')->never(); // inner must NOT reset

        $this->container->instance(ManagerRegistry::class, $registry);

        $service = new DoctrineTransactionService('default');

        $outerCaughtException = null;
        $result = $service->transaction(function () use ($service, &$outerCaughtException) {
            try {
                $service->transaction(function () {
                    throw new \RuntimeException('lock acquisition failed');
                });
            } catch (\RuntimeException $e) {
                $outerCaughtException = $e;
                // Outer catches and continues (e.g. skips email dispatch)
                return 'skipped';
            }
        });

        $this->assertNotNull($outerCaughtException);
        $this->assertSame('lock acquisition failed', $outerCaughtException->getMessage());
        $this->assertSame('skipped', $result);
    }

    /**
     * Direct nesting with connection error in inner: inner must NOT retry
     * and must NOT do destructive recovery. Exception propagates to root
     * which then handles the reconnect logic.
     *
     * This is the exact scenario that was broken in the old code:
     * inner connection error would close/reset EM, destroying the outer TX.
     */
    public function testDirectNestingConnectionErrorInInnerDoesNotDestroyOuterTx(): void
    {
        $conn = Mockery::mock(Connection::class);
        $conn->shouldReceive('setNestTransactionsWithSavepoints')->byDefault();
        $conn->shouldReceive('setTransactionIsolation')->byDefault();

        $txActive = false;
        $conn->shouldReceive('isTransactionActive')->andReturnUsing(function () use (&$txActive) {
            return $txActive;
        });
        $conn->shouldReceive('beginTransaction')->andReturnUsing(function () use (&$txActive) {
            $txActive = true;
        });
        $conn->shouldReceive('commit')->byDefault();
        $conn->shouldReceive('rollBack')->byDefault();
        $conn->shouldReceive('close')->byDefault();

        $em = Mockery::mock(EntityManagerInterface::class);
        $em->shouldReceive('getConnection')->andReturn($conn)->byDefault();
        $em->shouldReceive('isOpen')->andReturn(true)->byDefault();
        $em->shouldReceive('flush')->byDefault();
        $em->shouldReceive('clear')->byDefault();
        $em->shouldReceive('close')->byDefault();

        $registry = Mockery::mock(ManagerRegistry::class);
        $registry->shouldReceive('getManager')->with('default')->andReturn($em)->byDefault();
        // resetManager will be called by the ROOT's retry, not by the nested tx
        $registry->shouldReceive('resetManager')->with('default')->andReturn($em)->byDefault();

        $this->container->instance(ManagerRegistry::class, $registry);

        $service = new DoctrineTransactionService('default');

        $outerCallCount = 0;
        $innerCallCount = 0;

        // The outer root transaction should catch the ConnectionLost from the inner,
        // and since it propagates through the root's inner try/catch,
        // the root will retry with reconnection.
        $result = $service->transaction(function () use ($service, &$outerCallCount, &$innerCallCount) {
            $outerCallCount++;
            if ($outerCallCount === 1) {
                // First outer attempt: inner throws connection error
                $service->transaction(function () use (&$innerCallCount) {
                    $innerCallCount++;
                    throw new TestRetryableException('packets out of order');
                });
            }
            // Second outer attempt (after retry): succeeds
            return 'recovered';
        });

        $this->assertSame('recovered', $result);
        $this->assertSame(2, $outerCallCount, 'Root should retry after inner connection error');
        $this->assertSame(1, $innerCallCount, 'Inner must NOT retry — only called once');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // INDIRECT NESTING: service method that opens its own transaction
    // called from within another transaction
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Simulates SummitOrderService.php:2287 calling CompanyService::addCompany()
     * from within its own transaction. addCompany() opens its own transaction()
     * which should run as nested.
     */
    public function testIndirectNestingServiceCallRunsAsNested(): void
    {
        $conn = Mockery::mock(Connection::class);
        $conn->shouldReceive('setNestTransactionsWithSavepoints')->byDefault();
        $conn->shouldReceive('setTransactionIsolation')->once(); // only root
        $conn->shouldReceive('close')->byDefault();

        $txActive = false;
        $conn->shouldReceive('isTransactionActive')->andReturnUsing(function () use (&$txActive) {
            return $txActive;
        });
        $conn->shouldReceive('beginTransaction')->andReturnUsing(function () use (&$txActive) {
            $txActive = true;
        });
        $conn->shouldReceive('commit')->byDefault();
        $conn->shouldReceive('rollBack')->byDefault();

        $em = Mockery::mock(EntityManagerInterface::class);
        $em->shouldReceive('getConnection')->andReturn($conn)->byDefault();
        $em->shouldReceive('isOpen')->andReturn(true)->byDefault();
        $em->shouldReceive('flush')->byDefault();
        $em->shouldReceive('clear')->byDefault();
        $em->shouldReceive('close')->byDefault();

        $registry = Mockery::mock(ManagerRegistry::class);
        $registry->shouldReceive('getManager')->with('default')->andReturn($em)->byDefault();
        $registry->shouldReceive('resetManager')->with('default')->andReturn($em)->byDefault();

        $this->container->instance(ManagerRegistry::class, $registry);

        $service = new DoctrineTransactionService('default');

        // Simulate CompanyService::addCompany() — has its own transaction() call
        $addCompany = function () use ($service) {
            return $service->transaction(function () {
                // Simulates: CompanyFactory::build + repository->add + return company
                return (object)['id' => 99, 'name' => 'Test Corp'];
            });
        };

        // Simulate SummitOrderService outer transaction calling addCompany
        $result = $service->transaction(function () use ($addCompany) {
            $company = $addCompany();
            // Caller uses the result (attendee->setCompany)
            return "assigned:{$company->name}";
        });

        $this->assertSame('assigned:Test Corp', $result);
    }

    /**
     * Simulates SummitService.php:3079 calling SpeakerService::addSpeaker()
     * from within a transaction. The inner addSpeaker() transaction flushes
     * (so IDs are available) but doesn't retry or reset EM.
     */
    public function testIndirectNestingInnerFlushGeneratesIds(): void
    {
        $conn = Mockery::mock(Connection::class);
        $conn->shouldReceive('setNestTransactionsWithSavepoints')->byDefault();
        $conn->shouldReceive('setTransactionIsolation')->byDefault();
        $conn->shouldReceive('close')->byDefault();

        $txActive = false;
        $conn->shouldReceive('isTransactionActive')->andReturnUsing(function () use (&$txActive) {
            return $txActive;
        });
        $conn->shouldReceive('beginTransaction')->andReturnUsing(function () use (&$txActive) {
            $txActive = true;
        });
        $conn->shouldReceive('commit')->byDefault();
        $conn->shouldReceive('rollBack')->byDefault();

        // Track flush calls — both root and nested should flush
        $flushCount = 0;
        $em = Mockery::mock(EntityManagerInterface::class);
        $em->shouldReceive('getConnection')->andReturn($conn)->byDefault();
        $em->shouldReceive('isOpen')->andReturn(true)->byDefault();
        $em->shouldReceive('flush')->andReturnUsing(function () use (&$flushCount) {
            $flushCount++;
        });
        $em->shouldReceive('clear')->byDefault();
        $em->shouldReceive('close')->byDefault();

        $registry = Mockery::mock(ManagerRegistry::class);
        $registry->shouldReceive('getManager')->with('default')->andReturn($em)->byDefault();
        $registry->shouldReceive('resetManager')->with('default')->andReturn($em)->byDefault();

        $this->container->instance(ManagerRegistry::class, $registry);

        $service = new DoctrineTransactionService('default');

        // Simulate SpeakerService::addSpeaker — its own transaction
        $addSpeaker = function (array $data) use ($service) {
            return $service->transaction(function () use ($data) {
                // After flush, auto-increment ID would be available
                return (object)['id' => 42, 'email' => $data['email']];
            });
        };

        $result = $service->transaction(function () use ($addSpeaker) {
            $speaker = $addSpeaker(['email' => 'speaker@example.com']);
            // Caller uses speaker ID immediately
            return "speaker_id:{$speaker->id}";
        });

        $this->assertSame('speaker_id:42', $result);
        // Nested flush (addSpeaker) + root flush = 2 total
        $this->assertSame(2, $flushCount, 'Both nested and root should flush');
    }

    /**
     * Simulates SponsorUserSyncService.php:171 calling
     * SummitSponsorService::addSponsorUser() which fails.
     * The inner error propagates to the outer without destroying EM.
     */
    public function testIndirectNestingInnerServiceErrorDoesNotDestroyOuter(): void
    {
        $conn = Mockery::mock(Connection::class);
        $conn->shouldReceive('setNestTransactionsWithSavepoints')->byDefault();
        $conn->shouldReceive('setTransactionIsolation')->byDefault();
        $conn->shouldReceive('close')->byDefault();

        $txActive = false;
        $conn->shouldReceive('isTransactionActive')->andReturnUsing(function () use (&$txActive) {
            return $txActive;
        });
        $conn->shouldReceive('beginTransaction')->andReturnUsing(function () use (&$txActive) {
            $txActive = true;
        });
        $conn->shouldReceive('commit')->byDefault();
        $conn->shouldReceive('rollBack')->byDefault();

        $em = Mockery::mock(EntityManagerInterface::class);
        $em->shouldReceive('getConnection')->andReturn($conn)->byDefault();
        $em->shouldReceive('isOpen')->andReturn(true)->byDefault();
        $em->shouldReceive('flush')->byDefault();
        $em->shouldReceive('clear')->byDefault();
        $em->shouldReceive('close')->byDefault();

        $registry = Mockery::mock(ManagerRegistry::class);
        $registry->shouldReceive('getManager')->with('default')->andReturn($em)->byDefault();
        $registry->shouldReceive('resetManager')->never(); // critical: must NOT reset

        $this->container->instance(ManagerRegistry::class, $registry);

        $service = new DoctrineTransactionService('default');

        // Simulate addSponsorUser that throws validation error
        $addSponsorUser = function () use ($service) {
            return $service->transaction(function () {
                throw new \InvalidArgumentException('Sponsor not found.');
            });
        };

        // Outer transaction catches the inner failure and handles gracefully
        $result = $service->transaction(function () use ($addSponsorUser) {
            try {
                $addSponsorUser();
            } catch (\InvalidArgumentException $e) {
                return "handled:{$e->getMessage()}";
            }
            return 'unreachable';
        });

        $this->assertSame('handled:Sponsor not found.', $result);
    }

    /**
     * Simulates ScheduleService.php:510 calling SummitService::publishEvent()
     * from within a transaction. publishEvent() has its own transaction that
     * runs as nested — verifies the full happy-path flow with multiple
     * indirect nested calls in sequence.
     */
    public function testIndirectNestingMultipleNestedCallsInSequence(): void
    {
        $conn = Mockery::mock(Connection::class);
        $conn->shouldReceive('setNestTransactionsWithSavepoints')->byDefault();
        $conn->shouldReceive('setTransactionIsolation')->once(); // only root
        $conn->shouldReceive('close')->byDefault();

        $txActive = false;
        $conn->shouldReceive('isTransactionActive')->andReturnUsing(function () use (&$txActive) {
            return $txActive;
        });
        $conn->shouldReceive('beginTransaction')->andReturnUsing(function () use (&$txActive) {
            $txActive = true;
        });
        $conn->shouldReceive('commit')->byDefault();
        $conn->shouldReceive('rollBack')->byDefault();

        $em = Mockery::mock(EntityManagerInterface::class);
        $em->shouldReceive('getConnection')->andReturn($conn)->byDefault();
        $em->shouldReceive('isOpen')->andReturn(true)->byDefault();
        $em->shouldReceive('flush')->byDefault();
        $em->shouldReceive('clear')->byDefault();
        $em->shouldReceive('close')->byDefault();

        $registry = Mockery::mock(ManagerRegistry::class);
        $registry->shouldReceive('getManager')->with('default')->andReturn($em)->byDefault();
        $registry->shouldReceive('resetManager')->never();

        $this->container->instance(ManagerRegistry::class, $registry);

        $service = new DoctrineTransactionService('default');

        // Simulate multiple service calls (TagService::addTag, SpeakerService::addSpeaker)
        $addTag = function (string $name) use ($service) {
            return $service->transaction(function () use ($name) {
                return (object)['id' => rand(1, 100), 'tag' => $name];
            });
        };

        $addSpeaker = function (string $email) use ($service) {
            return $service->transaction(function () use ($email) {
                return (object)['id' => rand(1, 100), 'email' => $email];
            });
        };

        $publishEvent = function () use ($service) {
            return $service->transaction(function () {
                return 'published';
            });
        };

        // Outer transaction makes multiple service calls (simulates SummitSubmissionInvitationService)
        $result = $service->transaction(function () use ($addTag, $addSpeaker, $publishEvent) {
            $tag1 = $addTag('cloud');
            $tag2 = $addTag('kubernetes');
            $speaker = $addSpeaker('speaker@test.org');
            $status = $publishEvent();
            return "{$tag1->tag},{$tag2->tag},{$speaker->email},{$status}";
        });

        $this->assertSame('cloud,kubernetes,speaker@test.org,published', $result);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // EDGE CASES
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Root transaction exhausts max retries on persistent connection errors.
     */
    public function testRootTransactionThrowsAfterMaxRetries(): void
    {
        $conn = Mockery::mock(Connection::class);
        $conn->shouldReceive('isTransactionActive')->andReturn(false, true);
        $conn->shouldReceive('setNestTransactionsWithSavepoints')->byDefault();
        $conn->shouldReceive('setTransactionIsolation')->byDefault();
        $conn->shouldReceive('beginTransaction')->byDefault();
        $conn->shouldReceive('commit')->never();
        $conn->shouldReceive('rollBack')->byDefault();
        $conn->shouldReceive('close')->byDefault();

        $em = Mockery::mock(EntityManagerInterface::class);
        $em->shouldReceive('getConnection')->andReturn($conn)->byDefault();
        $em->shouldReceive('isOpen')->andReturn(true)->byDefault();
        $em->shouldReceive('flush')->never();
        $em->shouldReceive('clear')->byDefault();
        $em->shouldReceive('close')->byDefault();

        $registry = Mockery::mock(ManagerRegistry::class);
        $registry->shouldReceive('getManager')->with('default')->andReturn($em)->byDefault();
        $registry->shouldReceive('resetManager')->with('default')->andReturn($em)->byDefault();

        $this->container->instance(ManagerRegistry::class, $registry);

        $callCount = 0;
        $service = new DoctrineTransactionService('default');

        $this->expectException(TestRetryableException::class);

        $service->transaction(function () use (&$callCount) {
            $callCount++;
            throw new TestRetryableException('persistent failure');
        });

        // Should have been called MaxRetries times
        $this->assertSame(DoctrineTransactionService::MaxRetries, $callCount);
    }

    /**
     * Nested transaction that returns null — ensures null is properly propagated.
     * Covers the SummitPromoCodeService pattern where inner TX returns null
     * when promo code is not the expected type.
     */
    public function testNestedTransactionReturnsNull(): void
    {
        [$em, $conn] = $this->buildMocks(transactionActive: true);

        $service = new DoctrineTransactionService('default');
        $result = $service->transaction(function () {
            return null;
        });

        $this->assertNull($result);
    }

    /**
     * Root transaction with closed EntityManager — should reset before proceeding.
     */
    public function testRootTransactionResetsClosedEntityManager(): void
    {
        $conn = Mockery::mock(Connection::class);
        $conn->shouldReceive('isTransactionActive')->andReturn(false);
        $conn->shouldReceive('setNestTransactionsWithSavepoints')->byDefault();
        $conn->shouldReceive('setTransactionIsolation')->byDefault();
        $conn->shouldReceive('beginTransaction')->byDefault();
        $conn->shouldReceive('commit')->byDefault();
        $conn->shouldReceive('rollBack')->byDefault();
        $conn->shouldReceive('close')->byDefault();

        $em = Mockery::mock(EntityManagerInterface::class);
        $em->shouldReceive('getConnection')->andReturn($conn)->byDefault();
        $em->shouldReceive('isOpen')->andReturn(false); // EM is closed
        $em->shouldReceive('flush')->byDefault();
        $em->shouldReceive('clear')->byDefault();
        $em->shouldReceive('close')->byDefault();

        $freshEm = Mockery::mock(EntityManagerInterface::class);
        $freshEm->shouldReceive('getConnection')->andReturn($conn)->byDefault();
        $freshEm->shouldReceive('isOpen')->andReturn(true);
        $freshEm->shouldReceive('flush')->once();
        $freshEm->shouldReceive('clear')->byDefault();
        $freshEm->shouldReceive('close')->byDefault();

        $registry = Mockery::mock(ManagerRegistry::class);
        $registry->shouldReceive('getManager')->with('default')->andReturn($em)->byDefault();
        $registry->shouldReceive('resetManager')->with('default')->once()->andReturn($freshEm);

        $this->container->instance(ManagerRegistry::class, $registry);

        $service = new DoctrineTransactionService('default');
        $result = $service->transaction(function () {
            return 'from-fresh-em';
        });

        $this->assertSame('from-fresh-em', $result);
    }
}
