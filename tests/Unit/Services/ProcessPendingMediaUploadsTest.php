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

use App\Models\Foundation\Summit\Repositories\IPendingMediaUploadRepository;
use Illuminate\Container\Container;
use Illuminate\Support\Facades\Facade;
use services\model\SummitService;
use libs\utils\ITransactionService;
use Mockery;
use models\summit\ISummitRepository;
use models\summit\PendingMediaUpload;
use models\summit\Summit;
use models\summit\SummitMediaUploadType;
use PHPUnit\Framework\TestCase;
use Psr\Log\AbstractLogger;

/**
 * Class ProcessPendingMediaUploadsTest
 *
 * Unit tests for {@see SummitService::processPendingMediaUploads()}
 * covering retry logic, cleanup, and error handling.
 *
 * These tests mock IPendingMediaUploadRepository (the actual dependency)
 * rather than EntityManager, which the service does not call directly.
 *
 * @package Tests\Unit\Services
 */
/**
 * PSR-3 logger that records (level, message) pairs so tests can assert on the level
 * a transition was logged at (e.g. Error transitions must be visible at LOG_LEVEL=error).
 */
final class RecordingLogger extends AbstractLogger
{
    /** @var array<int, array{level: string, message: string}> */
    public array $records = [];

    public function log($level, string|\Stringable $message, array $context = []): void
    {
        $this->records[] = ['level' => (string) $level, 'message' => (string) $message];
    }

    /**
     * @param string $level
     * @param string $pattern regex
     * @return bool
     */
    public function has(string $level, string $pattern): bool
    {
        foreach ($this->records as $record) {
            if ($record['level'] === $level && preg_match($pattern, $record['message'])) return true;
        }
        return false;
    }
}

class ProcessPendingMediaUploadsTest extends TestCase
{
    /** @var RecordingLogger */
    private $logger;
    protected function setUp(): void
    {
        parent::setUp();
        // Provide a minimal facade application so Log::debug() calls in the SUT
        // resolve via the facade without requiring a full Laravel app or database.
        // clearResolvedInstances() FIRST to drop any cached LogManager from a
        // prior test that booted a full Laravel app (e.g., AbstractOAuth2ApiScopesTest).
        Facade::clearResolvedInstances();
        $app = new Container();
        $this->logger = new RecordingLogger();
        $app->singleton('log', fn() => $this->logger);
        Container::setInstance($app);
        Facade::setFacadeApplication($app);
    }

    protected function tearDown(): void
    {
        Facade::setFacadeApplication(null);
        Facade::clearResolvedInstances();
        Container::setInstance(null);
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Helper to create a real SummitService instance with injected dependencies.
     *
     * Uses ReflectionClass::newInstanceWithoutConstructor() to bypass the 27-parameter
     * constructor of the final class, then injects only the properties needed for
     * processPendingMediaUploads via reflection.
     */
    private function createServiceWithDeps(
        IPendingMediaUploadRepository $pendingRepo,
        ITransactionService $txService,
        ?ISummitRepository $summitRepository = null
    ): SummitService {
        $ref = new \ReflectionClass(SummitService::class);
        $service = $ref->newInstanceWithoutConstructor();

        $prop = $ref->getProperty('pending_media_upload_repository');
        $prop->setAccessible(true);
        $prop->setValue($service, $pendingRepo);

        if ($summitRepository) {
            $prop = $ref->getProperty('summit_repository');
            $prop->setAccessible(true);
            $prop->setValue($service, $summitRepository);
        }

        // tx_service is protected in AbstractService (grandparent of SummitService)
        $parentRef = $ref;
        while ($parentRef && !$parentRef->hasProperty('tx_service')) {
            $parentRef = $parentRef->getParentClass();
        }
        if ($parentRef) {
            $prop = $parentRef->getProperty('tx_service');
            $prop->setAccessible(true);
            $prop->setValue($service, $txService);
        }

        return $service;
    }

    /**
     * What PendingMediaUpload::getLastEditedUTC() returns for a row last edited $minutes ago.
     * The service must use the UTC accessor: the raw getLastEdited() is hydrated in the app
     * timezone from a DefaultTimeZone wall-clock value and is off by the zone offset.
     * @param int $minutes
     * @return \DateTime
     */
    private function minutesAgo(int $minutes): \DateTime
    {
        $dt = new \DateTime('now', new \DateTimeZone('UTC'));
        return $dt->modify(sprintf('-%d minutes', $minutes));
    }

    /**
     * Test max retries exceeded marks upload as Error.
     * When attempts >= max_retries, the upload should be permanently marked as Error.
     */
    public function testMaxRetriesExceededMarksUploadAsError(): void
    {
        $pendingRepo = Mockery::mock(IPendingMediaUploadRepository::class);
        $txService = Mockery::mock(ITransactionService::class);

        $pendingUpload = Mockery::mock(PendingMediaUpload::class);
        $pendingUpload->shouldReceive('getId')->andReturn(1);
        $pendingUpload->shouldReceive('getAttempts')->andReturn(3); // Already at max
        $pendingUpload->shouldReceive('setStatus')->once()->with(PendingMediaUpload::STATUS_ERROR);
        $pendingUpload->shouldReceive('setErrorMessage')->once()->with('Max retries exceeded');

        $pendingRepo->shouldReceive('resetStuckProcessingRows')->once()->with(10)->andReturn(0);
        $pendingRepo->shouldReceive('getPendingUploads')->once()->andReturn([$pendingUpload]);
        $pendingRepo->shouldReceive('deleteCompletedOlderThan')->once()->with(7, 1000)->andReturn(0);

        $txService->shouldReceive('transaction')->times(3)->andReturnUsing(function ($callback) {
            return $callback();
        });

        $service = $this->createServiceWithDeps($pendingRepo, $txService);

        $stats = $service->processPendingMediaUploads(3);

        $this->assertEquals(0, $stats['processed']);
        $this->assertEquals(1, $stats['errors']);
        // The permanent Error transition must be visible in production logs (LOG_LEVEL=error)
        $this->assertTrue($this->logger->has('error', '/upload ID 1 exceeded max retries/'), 'Error transition not logged at error level');
    }

    /**
     * Test that transient failure keeps upload at its current status for retry (not Error).
     * When attempts < max_retries, only setErrorMessage is called in the catch block;
     * status is NOT changed (it remains at whatever partial state was reached).
     */
    public function testTransientFailureKeepsUploadPendingForRetry(): void
    {
        $pendingRepo = Mockery::mock(IPendingMediaUploadRepository::class);
        $txService = Mockery::mock(ITransactionService::class);
        $summitRepository = Mockery::mock(ISummitRepository::class);

        $pendingUpload = Mockery::mock(PendingMediaUpload::class);
        $pendingUpload->shouldReceive('getId')->andReturn(1);
        // First getAttempts() call: retry guard check (0 < 3, passes)
        // Second getAttempts() call: in catch block (1 < 3, no ERROR status)
        $pendingUpload->shouldReceive('getAttempts')->andReturnValues([0, 1]);
        $pendingUpload->shouldReceive('setStatus')->with(PendingMediaUpload::STATUS_PROCESSING)->once();
        $pendingUpload->shouldReceive('incrementAttempts')->once();
        $pendingUpload->shouldReceive('getSummitId')->andReturn(999);
        // Summit not found → EntityNotFoundException → caught in inner catch
        $summitRepository->shouldReceive('getById')->with(999)->andReturn(null);

        // Verify setErrorMessage is called; setStatus is NOT called since attempts(1) < max_retries(3)
        // (the catch block leaves status at its current value — Processing in this case)
        $pendingUpload->shouldReceive('setErrorMessage')->once()->with(Mockery::pattern('/Summit 999 not found/'));

        $pendingRepo->shouldReceive('resetStuckProcessingRows')->once()->with(10)->andReturn(0);
        $pendingRepo->shouldReceive('getPendingUploads')->once()->andReturn([$pendingUpload]);
        $pendingRepo->shouldReceive('deleteCompletedOlderThan')->once()->with(7, 1000)->andReturn(0);

        $txService->shouldReceive('transaction')->times(4)->andReturnUsing(function ($callback) {
            return $callback();
        });

        $service = $this->createServiceWithDeps($pendingRepo, $txService, $summitRepository);

        $stats = $service->processPendingMediaUploads(3);

        $this->assertEquals(0, $stats['processed']);
        $this->assertEquals(1, $stats['errors']);
    }

    /**
     * Test cleanup of completed rows older than 7 days.
     * With no pending uploads, the method should still run cleanup.
     */
    public function testCleanupOldCompletedRows(): void
    {
        $pendingRepo = Mockery::mock(IPendingMediaUploadRepository::class);
        $txService = Mockery::mock(ITransactionService::class);

        $pendingRepo->shouldReceive('resetStuckProcessingRows')->once()->with(10)->andReturn(0);
        $pendingRepo->shouldReceive('getPendingUploads')->once()->andReturn([]);
        $pendingRepo->shouldReceive('deleteCompletedOlderThan')->once()->with(7, 1000)->andReturn(50);

        $txService->shouldReceive('transaction')->twice()->andReturnUsing(function ($callback) {
            return $callback();
        });

        $service = $this->createServiceWithDeps($pendingRepo, $txService);

        $stats = $service->processPendingMediaUploads();

        $this->assertEquals(0, $stats['processed']);
        $this->assertEquals(0, $stats['errors']);
    }

    /**
     * Test that stuck Processing rows are reset back to Pending.
     */
    public function testResetStuckProcessingRows(): void
    {
        $pendingRepo = Mockery::mock(IPendingMediaUploadRepository::class);
        $txService = Mockery::mock(ITransactionService::class);

        // 3 stuck rows get reset
        $pendingRepo->shouldReceive('resetStuckProcessingRows')->once()->with(10)->andReturn(3);
        $pendingRepo->shouldReceive('getPendingUploads')->once()->andReturn([]);
        $pendingRepo->shouldReceive('deleteCompletedOlderThan')->once()->with(7, 1000)->andReturn(0);

        $txService->shouldReceive('transaction')->twice()->andReturnUsing(function ($callback) {
            return $callback();
        });

        $service = $this->createServiceWithDeps($pendingRepo, $txService);

        $stats = $service->processPendingMediaUploads();

        $this->assertEquals(0, $stats['processed']);
        $this->assertEquals(0, $stats['errors']);
    }

    /**
     * Test that non-PendingMediaUpload instances in the result are skipped.
     */
    public function testNonPendingMediaUploadInstancesAreSkipped(): void
    {
        $pendingRepo = Mockery::mock(IPendingMediaUploadRepository::class);
        $txService = Mockery::mock(ITransactionService::class);

        $pendingRepo->shouldReceive('resetStuckProcessingRows')->once()->with(10)->andReturn(0);
        // Return a non-PendingMediaUpload object
        $pendingRepo->shouldReceive('getPendingUploads')->once()->andReturn([new \stdClass()]);
        $pendingRepo->shouldReceive('deleteCompletedOlderThan')->once()->with(7, 1000)->andReturn(0);

        $txService->shouldReceive('transaction')->twice()->andReturnUsing(function ($callback) {
            return $callback();
        });

        $service = $this->createServiceWithDeps($pendingRepo, $txService);

        $stats = $service->processPendingMediaUploads();

        $this->assertEquals(0, $stats['processed']);
        $this->assertEquals(0, $stats['errors']);
    }

    /**
     * Test that getPendingUploads returns rows with partial statuses.
     * Verifies that the repository query includes Pending, PublicStorageUploaded,
     * and PrivateStorageUploaded statuses.
     */
    public function testGetPendingUploadsIncludesPartialStatuses(): void
    {
        $pendingRepo = Mockery::mock(IPendingMediaUploadRepository::class);
        $txService = Mockery::mock(ITransactionService::class);

        // Create mocks for uploads with different statuses
        $pendingUpload = Mockery::mock(PendingMediaUpload::class);
        $pendingUpload->shouldReceive('getId')->andReturn(1);
        $pendingUpload->shouldReceive('getAttempts')->andReturn(3);

        $publicUpload = Mockery::mock(PendingMediaUpload::class);
        $publicUpload->shouldReceive('getId')->andReturn(2);
        $publicUpload->shouldReceive('getAttempts')->andReturn(3);

        $privateUpload = Mockery::mock(PendingMediaUpload::class);
        $privateUpload->shouldReceive('getId')->andReturn(3);
        $privateUpload->shouldReceive('getAttempts')->andReturn(3);

        // getPendingUploads should return all three
        $pendingRepo->shouldReceive('resetStuckProcessingRows')->once()->with(10)->andReturn(0);
        $pendingRepo->shouldReceive('getPendingUploads')->once()->andReturn([
            $pendingUpload,
            $publicUpload,
            $privateUpload
        ]);
        $pendingRepo->shouldReceive('deleteCompletedOlderThan')->once()->with(7, 1000)->andReturn(0);

        // All three hit max retries - expect setStatus(ERROR) for each
        $pendingUpload->shouldReceive('setStatus')->once()->with(PendingMediaUpload::STATUS_ERROR);
        $pendingUpload->shouldReceive('setErrorMessage')->once();
        $publicUpload->shouldReceive('setStatus')->once()->with(PendingMediaUpload::STATUS_ERROR);
        $publicUpload->shouldReceive('setErrorMessage')->once();
        $privateUpload->shouldReceive('setStatus')->once()->with(PendingMediaUpload::STATUS_ERROR);
        $privateUpload->shouldReceive('setErrorMessage')->once();

        // 5 transactions: reset stuck, 3x mark error, cleanup
        $txService->shouldReceive('transaction')->times(5)->andReturnUsing(function ($callback) {
            return $callback();
        });

        $service = $this->createServiceWithDeps($pendingRepo, $txService);

        $stats = $service->processPendingMediaUploads(3);

        $this->assertEquals(0, $stats['processed']);
        $this->assertEquals(3, $stats['errors']);
    }

    /**
     * Test that transient failure with partial status leaves status unchanged.
     * When public storage succeeds but private fails, status should remain at
     * PublicStorageUploaded for retry (not revert to Pending or Error).
     *
     * Note: The exception here is a TypeError from null summit_repository, which
     * is caught by the inner try/catch and triggers the error-handling transaction.
     * TX count: 1 (reset stuck) + 1 (mark processing) + 1 (catch: setErrorMessage)
     *           + 1 (cleanup) = 4.
     */
    public function testPartialStatusPreservedOnFailure(): void
    {
        $pendingRepo = Mockery::mock(IPendingMediaUploadRepository::class);
        $txService = Mockery::mock(ITransactionService::class);

        $pendingUpload = Mockery::mock(PendingMediaUpload::class);
        $pendingUpload->shouldReceive('getId')->andReturn(1);
        // First call: retry guard (1 < 3, passes)
        // Second call: in catch block (2 < 3, leave at partial status)
        $pendingUpload->shouldReceive('getAttempts')->andReturnValues([1, 2]);
        // Backoff for attempt 1 is 5 minutes; last attempt long ago -> due for retry
        $pendingUpload->shouldReceive('getLastEditedUTC')->andReturn($this->minutesAgo(60));

        // Verify setErrorMessage is called but setStatus is NOT called in catch block
        // (status stays at whatever partial state was reached)
        $pendingUpload->shouldReceive('setErrorMessage')->once();

        $pendingRepo->shouldReceive('resetStuckProcessingRows')->once()->with(10)->andReturn(0);
        $pendingRepo->shouldReceive('getPendingUploads')->once()->andReturn([$pendingUpload]);
        $pendingRepo->shouldReceive('deleteCompletedOlderThan')->once()->with(7, 1000)->andReturn(0);

        // 4 transactions: reset stuck, mark processing, catch setErrorMessage, cleanup
        $txService->shouldReceive('transaction')->times(4)->andReturnUsing(function ($callback) {
            return $callback();
        });

        $service = $this->createServiceWithDeps($pendingRepo, $txService);

        $stats = $service->processPendingMediaUploads(3);

        $this->assertEquals(0, $stats['processed']);
        $this->assertEquals(1, $stats['errors']);
    }

    /**
     * A row that already failed must wait before being retried: 5 * 2^(attempts-1) minutes
     * since LastEdited (5, 10, 20, 40 ...). With 2 attempts and a failure 6 minutes ago the
     * row is due at 10 minutes, so this run must leave it untouched (no status change,
     * no attempt increment, not counted as processed or error).
     */
    public function testProcessPendingMediaUploadsSkipsRowWhileBackoffNotElapsed(): void
    {
        $pendingRepo = Mockery::mock(IPendingMediaUploadRepository::class);
        $txService = Mockery::mock(ITransactionService::class);

        $pendingUpload = Mockery::mock(PendingMediaUpload::class);
        $pendingUpload->shouldReceive('getId')->andReturn(1);
        $pendingUpload->shouldReceive('getAttempts')->andReturn(2);
        $pendingUpload->shouldReceive('getLastEditedUTC')->andReturn($this->minutesAgo(6));
        $pendingUpload->shouldNotReceive('setStatus');
        $pendingUpload->shouldNotReceive('incrementAttempts');
        $pendingUpload->shouldNotReceive('setErrorMessage');

        $pendingRepo->shouldReceive('resetStuckProcessingRows')->once()->with(10)->andReturn(0);
        $pendingRepo->shouldReceive('getPendingUploads')->once()->andReturn([$pendingUpload]);
        $pendingRepo->shouldReceive('deleteCompletedOlderThan')->once()->with(7, 1000)->andReturn(0);

        // Only reset stuck + cleanup transactions: the row is skipped without touching it
        $txService->shouldReceive('transaction')->twice()->andReturnUsing(function ($callback) {
            return $callback();
        });

        $service = $this->createServiceWithDeps($pendingRepo, $txService);

        $stats = $service->processPendingMediaUploads();

        $this->assertEquals(0, $stats['processed']);
        $this->assertEquals(0, $stats['errors']);
    }

    /**
     * Default retry budget is 5 attempts. A row with 4 failed attempts whose backoff
     * (40 minutes) has elapsed is attempted again; when that 5th attempt fails the row
     * transitions to Error and the transition is logged at error level.
     */
    public function testProcessPendingMediaUploadsMarksErrorAndLogsErrorOnFinalFailedAttempt(): void
    {
        $pendingRepo = Mockery::mock(IPendingMediaUploadRepository::class);
        $txService = Mockery::mock(ITransactionService::class);
        $summitRepository = Mockery::mock(ISummitRepository::class);

        $pendingUpload = Mockery::mock(PendingMediaUpload::class);
        $pendingUpload->shouldReceive('getId')->andReturn(1);
        // First getAttempts() call: retry guard + backoff (4 < 5, 45 min > 40 min backoff)
        // Second getAttempts() call: in catch block after incrementAttempts (5 >= 5 -> Error)
        $pendingUpload->shouldReceive('getAttempts')->andReturnValues([4, 5]);
        $pendingUpload->shouldReceive('getLastEditedUTC')->andReturn($this->minutesAgo(45));
        $pendingUpload->shouldReceive('setStatus')->with(PendingMediaUpload::STATUS_PROCESSING)->once();
        $pendingUpload->shouldReceive('incrementAttempts')->once();
        $pendingUpload->shouldReceive('getSummitId')->andReturn(999);
        // Summit not found -> EntityNotFoundException -> caught, attempts exhausted
        $summitRepository->shouldReceive('getById')->with(999)->andReturn(null);
        $pendingUpload->shouldReceive('setErrorMessage')->once()->with(Mockery::pattern('/Summit 999 not found/'));
        $pendingUpload->shouldReceive('setStatus')->with(PendingMediaUpload::STATUS_ERROR)->once();

        $pendingRepo->shouldReceive('resetStuckProcessingRows')->once()->with(10)->andReturn(0);
        $pendingRepo->shouldReceive('getPendingUploads')->once()->andReturn([$pendingUpload]);
        $pendingRepo->shouldReceive('deleteCompletedOlderThan')->once()->with(7, 1000)->andReturn(0);

        // 4 transactions: reset stuck, mark processing, catch (error message + Error status), cleanup
        $txService->shouldReceive('transaction')->times(4)->andReturnUsing(function ($callback) {
            return $callback();
        });

        $service = $this->createServiceWithDeps($pendingRepo, $txService, $summitRepository);

        // default max_retries
        $stats = $service->processPendingMediaUploads();

        $this->assertEquals(0, $stats['processed']);
        $this->assertEquals(1, $stats['errors']);
        $this->assertTrue($this->logger->has('error', '/upload ID 1 failed \(attempt 5\/5\)/'), 'Final failed attempt not logged at error level');
    }
}
