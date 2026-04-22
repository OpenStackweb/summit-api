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
use App\Services\Model\Imp\SummitService;
use libs\utils\ITransactionService;
use Mockery;
use models\summit\ISummitRepository;
use models\summit\PendingMediaUpload;
use models\summit\Summit;
use models\summit\SummitMediaUploadType;
use PHPUnit\Framework\TestCase;

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
class ProcessPendingMediaUploadsTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Helper to create a SummitService partial mock with injected dependencies.
     * Uses reflection to set private properties since the constructor has many parameters.
     */
    private function createServiceWithDeps(
        IPendingMediaUploadRepository $pendingRepo,
        ITransactionService $txService,
        ?ISummitRepository $summitRepository = null
    ): SummitService {
        $service = Mockery::mock(SummitService::class)->makePartial();
        $service->shouldAllowMockingProtectedMethods();

        // Inject private dependencies via reflection
        $ref = new \ReflectionClass(SummitService::class);

        $prop = $ref->getProperty('pending_media_upload_repository');
        $prop->setAccessible(true);
        $prop->setValue($service, $pendingRepo);

        if ($summitRepository) {
            $prop = $ref->getProperty('summit_repository');
            $prop->setAccessible(true);
            $prop->setValue($service, $summitRepository);
        }

        // tx_service is protected in AbstractService (grandparent)
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
    }

    /**
     * Test that transient failure keeps upload as Pending for retry (not Error).
     * When attempts < max_retries, the status should revert to Pending so the next
     * cron run picks it up again.
     */
    public function testTransientFailureKeepsUploadPendingForRetry(): void
    {
        $pendingRepo = Mockery::mock(IPendingMediaUploadRepository::class);
        $txService = Mockery::mock(ITransactionService::class);
        $summitRepository = Mockery::mock(ISummitRepository::class);

        $pendingUpload = Mockery::mock(PendingMediaUpload::class);
        $pendingUpload->shouldReceive('getId')->andReturn(1);
        // First getAttempts() call: retry guard check (0 < 3, passes)
        // Second getAttempts() call: in catch block (1 < 3, stays Pending)
        $pendingUpload->shouldReceive('getAttempts')->andReturnValues([0, 1]);
        $pendingUpload->shouldReceive('setStatus')->with(PendingMediaUpload::STATUS_PROCESSING)->once();
        $pendingUpload->shouldReceive('incrementAttempts')->once();
        $pendingUpload->shouldReceive('getSummitId')->andReturn(999);
        // Summit not found → EntityNotFoundException → caught in inner catch
        $summitRepository->shouldReceive('getById')->with(999)->andReturn(null);

        // Verify it stays Pending (not Error) since attempts(1) < max_retries(3)
        $pendingUpload->shouldReceive('setErrorMessage')->once()->with(Mockery::pattern('/Summit 999 not found/'));
        $pendingUpload->shouldReceive('setStatus')->with(PendingMediaUpload::STATUS_PENDING)->once();

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
}
