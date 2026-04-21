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

use App\Services\Model\Imp\SummitService;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\AbstractQuery;
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
 * 429 rate-limit handling is now tested in RetryAfterDropboxClientTest.
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
     * Test successful upload processing flow
     */
    public function testProcessPendingMediaUploadsSuccessfulFlow(): void
    {
        $em = Mockery::mock(EntityManager::class);
        $query = Mockery::mock(AbstractQuery::class);
        $pendingUpload = Mockery::mock(PendingMediaUpload::class);
        $summit = Mockery::mock(Summit::class);
        $mediaUploadType = Mockery::mock(SummitMediaUploadType::class);
        $summitRepository = Mockery::mock(ISummitRepository::class);
        $txService = Mockery::mock(ITransactionService::class);

        // Setup reset query for stuck Processing rows
        $resetQuery = Mockery::mock(AbstractQuery::class);
        $em->shouldReceive('createQuery')
            ->once()
            ->with(Mockery::pattern('/UPDATE.*Processing/'))
            ->andReturn($resetQuery);
        $resetQuery->shouldReceive('setParameter')->andReturnSelf();
        $resetQuery->shouldReceive('execute')->andReturn(0);

        // Setup main query for pending uploads
        $em->shouldReceive('createQuery')
            ->once()
            ->with(Mockery::pattern('/SELECT.*Pending/'))
            ->andReturn($query);
        $query->shouldReceive('setParameter')->with('status', PendingMediaUpload::STATUS_PENDING)->andReturnSelf();
        $query->shouldReceive('getResult')->andReturn([$pendingUpload]);

        $pendingUpload->shouldReceive('getId')->andReturn(1);
        $pendingUpload->shouldReceive('getAttempts')->andReturn(0);
        $pendingUpload->shouldReceive('setStatus')->with(PendingMediaUpload::STATUS_PROCESSING);
        $pendingUpload->shouldReceive('incrementAttempts');
        $pendingUpload->shouldReceive('getSummitId')->andReturn(1);
        $pendingUpload->shouldReceive('getMediaUploadTypeId')->andReturn(1);
        $pendingUpload->shouldReceive('getFileName')->andReturn('test.pdf');
        $pendingUpload->shouldReceive('getTempFilePath')->andReturn('/tmp/test.pdf');
        $pendingUpload->shouldReceive('getPrivatePath')->andReturn('private/test.pdf');
        $pendingUpload->shouldReceive('getPublicPath')->andReturn('public/test.pdf');
        $pendingUpload->shouldReceive('setStatus')->with(PendingMediaUpload::STATUS_COMPLETED);
        $pendingUpload->shouldReceive('setProcessedDate');

        $summitRepository->shouldReceive('getById')->with(1)->andReturn($summit);
        $summit->shouldReceive('getId')->andReturn(1);
        $summit->shouldReceive('getMediaUploadTypeById')->with(1)->andReturn($mediaUploadType);

        $mediaUploadType->shouldReceive('getPrivateStorageType')->andReturn(null);
        $mediaUploadType->shouldReceive('getPublicStorageType')->andReturn(null);

        $txService->shouldReceive('transaction')->times(3)->andReturnUsing(function ($callback) {
            return $callback();
        });

        // Cleanup query
        $cleanupQuery = Mockery::mock(AbstractQuery::class);
        $em->shouldReceive('createQuery')
            ->once()
            ->with(Mockery::pattern('/DELETE.*PendingMediaUpload/'))
            ->andReturn($cleanupQuery);
        $cleanupQuery->shouldReceive('setParameter')->andReturnSelf();
        $cleanupQuery->shouldReceive('setMaxResults')->with(1000)->andReturnSelf();
        $cleanupQuery->shouldReceive('execute')->andReturn(0);

        // Create service with minimal mocked dependencies
        $service = Mockery::mock(SummitService::class)->makePartial();
        $service->shouldReceive('getEM')->andReturn($em);
        $service->shouldAllowMockingProtectedMethods();
        $service->tx_service = $txService;
        $service->summit_repository = $summitRepository;

        $stats = $service->processPendingMediaUploads();

        $this->assertEquals(1, $stats['processed']);
        $this->assertEquals(0, $stats['errors']);
    }

    /**
     * Test max retries exceeded marking upload as error
     */
    public function testProcessPendingMediaUploadsMaxRetriesExceeded(): void
    {
        $em = Mockery::mock(EntityManager::class);
        $query = Mockery::mock(AbstractQuery::class);
        $pendingUpload = Mockery::mock(PendingMediaUpload::class);
        $txService = Mockery::mock(ITransactionService::class);

        // Setup reset query
        $resetQuery = Mockery::mock(AbstractQuery::class);
        $em->shouldReceive('createQuery')
            ->once()
            ->with(Mockery::pattern('/UPDATE.*Processing/'))
            ->andReturn($resetQuery);
        $resetQuery->shouldReceive('setParameter')->andReturnSelf();
        $resetQuery->shouldReceive('execute')->andReturn(0);

        // Setup main query
        $em->shouldReceive('createQuery')
            ->once()
            ->with(Mockery::pattern('/SELECT.*Pending/'))
            ->andReturn($query);
        $query->shouldReceive('setParameter')->with('status', PendingMediaUpload::STATUS_PENDING)->andReturnSelf();
        $query->shouldReceive('getResult')->andReturn([$pendingUpload]);

        $pendingUpload->shouldReceive('getId')->andReturn(1);
        $pendingUpload->shouldReceive('getAttempts')->andReturn(3); // Already at max
        $pendingUpload->shouldReceive('setStatus')->with(PendingMediaUpload::STATUS_ERROR);
        $pendingUpload->shouldReceive('setErrorMessage')->with('Max retries exceeded');

        $txService->shouldReceive('transaction')->twice()->andReturnUsing(function ($callback) {
            return $callback();
        });

        // Setup cleanup query
        $cleanupQuery = Mockery::mock(AbstractQuery::class);
        $em->shouldReceive('createQuery')
            ->once()
            ->with(Mockery::pattern('/DELETE.*PendingMediaUpload/'))
            ->andReturn($cleanupQuery);
        $cleanupQuery->shouldReceive('setParameter')->andReturnSelf();
        $cleanupQuery->shouldReceive('setMaxResults')->with(1000)->andReturnSelf();
        $cleanupQuery->shouldReceive('execute')->andReturn(0);

        $service = Mockery::mock(SummitService::class)->makePartial();
        $service->shouldReceive('getEM')->andReturn($em);
        $service->tx_service = $txService;

        $stats = $service->processPendingMediaUploads(3);

        $this->assertEquals(0, $stats['processed']);
        $this->assertEquals(1, $stats['errors']);
    }

    /**
     * Test entity not found error handling
     */
    public function testProcessPendingMediaUploadsSummitNotFound(): void
    {
        $em = Mockery::mock(EntityManager::class);
        $query = Mockery::mock(AbstractQuery::class);
        $pendingUpload = Mockery::mock(PendingMediaUpload::class);
        $summitRepository = Mockery::mock(ISummitRepository::class);
        $txService = Mockery::mock(ITransactionService::class);

        // Setup reset query
        $resetQuery = Mockery::mock(AbstractQuery::class);
        $em->shouldReceive('createQuery')
            ->once()
            ->with(Mockery::pattern('/UPDATE.*Processing/'))
            ->andReturn($resetQuery);
        $resetQuery->shouldReceive('setParameter')->andReturnSelf();
        $resetQuery->shouldReceive('execute')->andReturn(0);

        // Setup main query
        $em->shouldReceive('createQuery')
            ->once()
            ->with(Mockery::pattern('/SELECT.*Pending/'))
            ->andReturn($query);
        $query->shouldReceive('setParameter')->with('status', PendingMediaUpload::STATUS_PENDING)->andReturnSelf();
        $query->shouldReceive('getResult')->andReturn([$pendingUpload]);

        $pendingUpload->shouldReceive('getId')->andReturn(1);
        $pendingUpload->shouldReceive('getAttempts')->andReturn(0);
        $pendingUpload->shouldReceive('setStatus')->with(PendingMediaUpload::STATUS_PROCESSING);
        $pendingUpload->shouldReceive('incrementAttempts');
        $pendingUpload->shouldReceive('getSummitId')->andReturn(999);
        $pendingUpload->shouldReceive('setStatus')->with(PendingMediaUpload::STATUS_ERROR);
        $pendingUpload->shouldReceive('setErrorMessage')->with(Mockery::pattern('/Summit 999 not found/'));

        $summitRepository->shouldReceive('getById')->with(999)->andReturn(null);

        $txService->shouldReceive('transaction')->times(3)->andReturnUsing(function ($callback) {
            return $callback();
        });

        // Setup cleanup query
        $cleanupQuery = Mockery::mock(AbstractQuery::class);
        $em->shouldReceive('createQuery')
            ->once()
            ->with(Mockery::pattern('/DELETE.*PendingMediaUpload/'))
            ->andReturn($cleanupQuery);
        $cleanupQuery->shouldReceive('setParameter')->andReturnSelf();
        $cleanupQuery->shouldReceive('setMaxResults')->with(1000)->andReturnSelf();
        $cleanupQuery->shouldReceive('execute')->andReturn(0);

        $service = Mockery::mock(SummitService::class)->makePartial();
        $service->shouldReceive('getEM')->andReturn($em);
        $service->tx_service = $txService;
        $service->summit_repository = $summitRepository;

        $stats = $service->processPendingMediaUploads();

        $this->assertEquals(0, $stats['processed']);
        $this->assertEquals(1, $stats['errors']);
    }

    /**
     * Test cleanup of completed rows older than 7 days
     */
    public function testProcessPendingMediaUploadsCleanupOldCompletedRows(): void
    {
        $em = Mockery::mock(EntityManager::class);
        $query = Mockery::mock(AbstractQuery::class);
        $txService = Mockery::mock(ITransactionService::class);

        // Setup reset query
        $resetQuery = Mockery::mock(AbstractQuery::class);
        $em->shouldReceive('createQuery')
            ->once()
            ->with(Mockery::pattern('/UPDATE.*Processing/'))
            ->andReturn($resetQuery);
        $resetQuery->shouldReceive('setParameter')->andReturnSelf();
        $resetQuery->shouldReceive('execute')->andReturn(0);

        // Setup main query - no pending uploads
        $em->shouldReceive('createQuery')
            ->once()
            ->with(Mockery::pattern('/SELECT.*Pending/'))
            ->andReturn($query);
        $query->shouldReceive('setParameter')->with('status', PendingMediaUpload::STATUS_PENDING)->andReturnSelf();
        $query->shouldReceive('getResult')->andReturn([]);

        $txService->shouldReceive('transaction')->twice()->andReturnUsing(function ($callback) {
            return $callback();
        });

        // Setup cleanup query - simulate deleting 50 old rows
        $cleanupQuery = Mockery::mock(AbstractQuery::class);
        $em->shouldReceive('createQuery')
            ->once()
            ->with(Mockery::pattern('/DELETE.*PendingMediaUpload/'))
            ->andReturn($cleanupQuery);
        $cleanupQuery->shouldReceive('setParameter')->andReturnSelf();
        $cleanupQuery->shouldReceive('setMaxResults')->with(1000)->andReturnSelf();
        $cleanupQuery->shouldReceive('execute')->andReturn(50);

        $service = Mockery::mock(SummitService::class)->makePartial();
        $service->shouldReceive('getEM')->andReturn($em);
        $service->tx_service = $txService;

        $stats = $service->processPendingMediaUploads();

        $this->assertEquals(0, $stats['processed']);
        $this->assertEquals(0, $stats['errors']);
    }
}
