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

use App\Jobs\CopyClonedMediaUploadFilesJob;
use App\Models\Foundation\Summit\Repositories\IPresentationMediaUploadRepository;
use App\Models\Utils\IStorageTypesConstants;
use App\Services\Utils\Exceptions\UnacquiredLockException;
use App\Services\Utils\ILockManagerService;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use models\summit\PendingMediaUpload;
use models\summit\Presentation;
use models\summit\PresentationMediaUpload;
use models\summit\SummitMediaFileType;
use models\summit\SummitMediaUploadType;
use services\model\ISummitService;
use services\model\SummitService;

/**
 * DB-backed: SummitService::copyClonedMediaUploadFiles / removeUncopiedClonedMediaUpload, run
 * through the job's handle()/failed(), reload both uploads by ID. Storage is the Local driver on a
 * faked disk.
 */
class CopyClonedMediaUploadFilesJobTest extends TestCase
{
    use InsertSummitTestData;

    private static SummitMediaUploadType $type;
    private static Presentation $original;
    private static Presentation $clonePresentation;

    protected function setUp(): void
    {
        parent::setUp();
        self::insertSummitTestData();
        Storage::fake('local');

        $file_type = new SummitMediaFileType();
        $file_type->setName('PPTX_' . rand(1, 100000));
        $file_type->setDescription('PPTX');
        $file_type->setAllowedExtensions('PPTX');
        self::$em->persist($file_type);

        self::$type = new SummitMediaUploadType();
        self::$type->setType($file_type);
        self::$type->setName('Deck');
        self::$type->setDescription('Deck');
        self::$type->setMaxSize(2048);
        self::$type->setMinUploadsQty(0);
        self::$type->setMaxUploadsQty(1);
        self::$type->setPrivateStorageType(IStorageTypesConstants::Local);
        self::$type->setPublicStorageType(IStorageTypesConstants::Local);
        self::$summit->addMediaUploadType(self::$type);

        $event_type = self::$summit->getEventTypes()[0];
        self::$original = new Presentation();
        self::$original->setTitle('Original');
        self::$original->setType($event_type);
        self::$summit->addEvent(self::$original);
        self::$clonePresentation = new Presentation();
        self::$clonePresentation->setTitle('Original');
        self::$clonePresentation->setType($event_type);
        self::$summit->addEvent(self::$clonePresentation);

        self::$em->persist(self::$summit);
        self::$em->flush();
    }

    protected function tearDown(): void
    {
        self::clearSummitTestData();
        parent::tearDown();
    }

    /** @return PresentationMediaUpload[] [$source, $target] */
    private function makePair(bool $modernSource = false): array
    {
        $source = new PresentationMediaUpload();
        $source->setName('deck');
        $source->setFilename('deck.pptx');
        $source->setMediaUploadType(self::$type);
        self::$original->addMediaUpload($source);
        if ($modernSource) {
            $r = new \ReflectionProperty(PresentationMediaUpload::class, 'legacy_path_format');
            $r->setAccessible(true);
            $r->setValue($source, false);
            $r = new \ReflectionProperty(PresentationMediaUpload::class, 'frozen_path_segment');
            $r->setAccessible(true);
            $r->setValue($source, '1-frozen/2-frozen-title');
        }
        $target = $source->clone();
        self::$clonePresentation->addMediaUpload($target);
        self::$em->flush();
        return [$source, $target];
    }

    private function seedSource(PresentationMediaUpload $source): void
    {
        Storage::disk('local')->put($source->getRelativePath(IStorageTypesConstants::PrivateType), 'private-bytes');
        Storage::disk('local')->put($source->getRelativePath(IStorageTypesConstants::PublicType), 'public-bytes');
    }

    /** What updateMediaUploadFrom writes for every re-upload, in the same transaction. */
    private function markReuploaded(PresentationMediaUpload $upload, string $status = PendingMediaUpload::STATUS_PENDING): void
    {
        $pending = new PendingMediaUpload();
        $pending->setSummitId(self::$summit->getId());
        $pending->setMediaUploadTypeId(self::$type->getId());
        $pending->setMediaUpload($upload);
        $pending->setPublicPath($upload->getPath(IStorageTypesConstants::PublicType));
        $pending->setPrivatePath($upload->getPath(IStorageTypesConstants::PrivateType));
        $pending->setFileName($upload->getFilename());
        $pending->setTempFilePath('/tmp/' . $upload->getFilename());
        $pending->setStatus($status);
        self::$em->persist($pending);
        self::$em->flush();
    }

    private function makeJob(int $source_id, int $target_id): CopyClonedMediaUploadFilesJob
    {
        return new CopyClonedMediaUploadFilesJob(
            $source_id, self::$original->getId(), $target_id, self::$clonePresentation->getId(), 'deck.pptx'
        );
    }

    /**
     * Deletes an upload's rows behind the EntityManager's back, as another process would: the
     * entity this process already loaded stays in the identity map.
     */
    private function deleteRowOutsideEntityManager(int $id): void
    {
        $connection = self::$em->getConnection();
        $connection->executeStatement('DELETE FROM PresentationMediaUpload WHERE ID = ?', [$id]);
        $connection->executeStatement('DELETE FROM PresentationMaterial WHERE ID = ?', [$id]);
    }

    private function runJob(CopyClonedMediaUploadFilesJob $job): void
    {
        $job->handle(App::make(ISummitService::class));
    }

    /** Holds an upload's storage lock, as processPendingMediaUploads does, while $fn runs. */
    private function withStorageLockHeld(PresentationMediaUpload $upload, \Closure $fn): void
    {
        $locks = App::make(ILockManagerService::class);
        $name = SummitService::mediaUploadStorageLockName($upload->getPresentation()->getId(), $upload->getFilename());
        $token = $locks->acquireLock($name, 60);
        try {
            $fn();
        } finally {
            $locks->releaseLock($name, $token);
        }
    }

    private function findUpload(int $id): ?PresentationMediaUpload
    {
        self::$em->clear();
        return \LaravelDoctrine\ORM\Facades\Registry::getManager(\models\utils\SilverstripeBaseModel::EntityManager)
            ->find(PresentationMediaUpload::class, $id);
    }

    public function testQueueSettingsKeepTheirOrdering(): void
    {
        $job = $this->makeJob(1, 2);
        // a slow attempt is never re-served while it still runs
        $this->assertLessThan(Config::get('queue.connections.redis.retry_after'), $job->timeout);
        $this->assertLessThan(Config::get('queue.connections.database.retry_after'), $job->timeout);
        // a killed attempt's locks outlive it, and a copy waiting on a lock outlasts that lock
        $this->assertGreaterThan($job->timeout, ISummitService::MEDIA_UPLOAD_STORAGE_LOCK_LIFETIME);
        $this->assertCount($job->tries - 1, $job->backoff);
        $this->assertGreaterThan(ISummitService::MEDIA_UPLOAD_STORAGE_LOCK_LIFETIME, array_sum($job->backoff));
    }

    public function testHandleCopiesBothLegsAndLeavesOriginal(): void
    {
        [$source, $target] = $this->makePair();
        $this->seedSource($source);

        $this->runJob($this->makeJob($source->getId(), $target->getId()));

        $disk = Storage::disk('local');
        $this->assertSame('private-bytes', $disk->get($target->getRelativePath(IStorageTypesConstants::PrivateType)));
        $this->assertSame('public-bytes', $disk->get($target->getRelativePath(IStorageTypesConstants::PublicType)));
        $this->assertSame('private-bytes', $disk->get($source->getRelativePath(IStorageTypesConstants::PrivateType)));
        $this->assertSame('public-bytes', $disk->get($source->getRelativePath(IStorageTypesConstants::PublicType)));
        $this->assertNotSame(
            $source->getRelativePath(IStorageTypesConstants::PrivateType),
            $target->getRelativePath(IStorageTypesConstants::PrivateType)
        );
    }

    public function testHandleCopiesFromModernSourceToLegacyClone(): void
    {
        [$source, $target] = $this->makePair(true);
        $this->seedSource($source);
        $this->assertStringContainsString('1-frozen/2-frozen-title', $source->getRelativePath(IStorageTypesConstants::PrivateType));

        $this->runJob($this->makeJob($source->getId(), $target->getId()));

        $expected = sprintf('%s/%s/deck.pptx', self::$summit->getId(), self::$clonePresentation->getId());
        $this->assertStringEndsWith($expected, $target->getRelativePath(IStorageTypesConstants::PrivateType));
        $this->assertSame('private-bytes', Storage::disk('local')->get($target->getRelativePath(IStorageTypesConstants::PrivateType)));
    }

    public function testHandleOverwritesPartialTargetFromEarlierAttempt(): void
    {
        [$source, $target] = $this->makePair();
        $this->seedSource($source);
        Storage::disk('local')->put($target->getRelativePath(IStorageTypesConstants::PrivateType), 'priv');

        $this->runJob($this->makeJob($source->getId(), $target->getId()));

        $disk = Storage::disk('local');
        $this->assertSame('private-bytes', $disk->get($target->getRelativePath(IStorageTypesConstants::PrivateType)));
        $this->assertSame('public-bytes', $disk->get($target->getRelativePath(IStorageTypesConstants::PublicType)));
    }

    public function testHandleSkipsStorageTypeNone(): void
    {
        self::$type->setPublicStorageType(IStorageTypesConstants::None);
        self::$em->flush();
        [$source, $target] = $this->makePair();
        Storage::disk('local')->put($source->getRelativePath(IStorageTypesConstants::PrivateType), 'private-bytes');

        $this->runJob($this->makeJob($source->getId(), $target->getId()));

        $this->assertSame('private-bytes', Storage::disk('local')->get($target->getRelativePath(IStorageTypesConstants::PrivateType)));
        $this->assertFalse(Storage::disk('local')->exists($target->getRelativePath(IStorageTypesConstants::PublicType)));
    }

    public function testHandleThrowsWhenSourceFileMissing(): void
    {
        [$source, $target] = $this->makePair();

        $this->expectException(\RuntimeException::class);
        $this->runJob($this->makeJob($source->getId(), $target->getId()));
    }

    /**
     * An original with only a private leg (storage types edited after upload; the public back-fill
     * only covers display_on_site uploads) still gives the clone the leg it has.
     */
    public function testHandleCopiesAvailableLegWhenOtherSourceLegMissing(): void
    {
        [$source, $target] = $this->makePair();
        Storage::disk('local')->put($source->getRelativePath(IStorageTypesConstants::PrivateType), 'private-bytes');

        $this->runJob($this->makeJob($source->getId(), $target->getId()));

        $disk = Storage::disk('local');
        $this->assertSame('private-bytes', $disk->get($target->getRelativePath(IStorageTypesConstants::PrivateType)));
        $this->assertFalse($disk->exists($target->getRelativePath(IStorageTypesConstants::PublicType)));
    }

    public function testHandleThrowsWhenSourceRecordGone(): void
    {
        [$source, $target] = $this->makePair();
        $this->seedSource($source);
        $source_id = $source->getId();
        self::$original->removeMediaUpload($source);
        self::$em->flush();

        $this->expectException(\RuntimeException::class);
        $this->runJob($this->makeJob($source_id, $target->getId()));
    }

    public function testHandleIsNoOpWhenTargetFilenameChanged(): void
    {
        [$source, $target] = $this->makePair();
        $this->seedSource($source);
        $target->setFilename('replaced.pptx');
        self::$em->flush();
        Storage::disk('local')->put($target->getRelativePath(IStorageTypesConstants::PrivateType), 'staff-bytes');

        $this->runJob($this->makeJob($source->getId(), $target->getId()));

        $disk = Storage::disk('local');
        $this->assertSame('staff-bytes', $disk->get($target->getRelativePath(IStorageTypesConstants::PrivateType)));
        $this->assertFalse($disk->exists($target->getRelativePath(IStorageTypesConstants::PublicType)));
    }

    public function testHandleIsNoOpWhenTargetReuploadedWithSameFilename(): void
    {
        [$source, $target] = $this->makePair();
        $this->seedSource($source);
        $this->markReuploaded($target);
        Storage::disk('local')->put($target->getRelativePath(IStorageTypesConstants::PrivateType), 'staff-bytes');

        $this->runJob($this->makeJob($source->getId(), $target->getId()));

        $disk = Storage::disk('local');
        $this->assertSame('staff-bytes', $disk->get($target->getRelativePath(IStorageTypesConstants::PrivateType)));
        $this->assertFalse($disk->exists($target->getRelativePath(IStorageTypesConstants::PublicType)));
    }

    public function testHandleIsNoOpWhenTargetGone(): void
    {
        [$source, $target] = $this->makePair();
        $this->seedSource($source);
        $target_id = $target->getId();
        self::$clonePresentation->removeMediaUpload($target);
        self::$em->flush();

        $this->runJob($this->makeJob($source->getId(), $target_id));

        $this->assertNull($this->findUpload($target_id));
    }

    public function testFailedRemovesCloneUploadAndKeepsOriginal(): void
    {
        [$source, $target] = $this->makePair();
        $this->seedSource($source);
        $source_private = $source->getRelativePath(IStorageTypesConstants::PrivateType);
        $target_private = $target->getRelativePath(IStorageTypesConstants::PrivateType);
        Storage::disk('local')->put($target_private, 'partial');
        $source_id = $source->getId();
        $target_id = $target->getId();

        ($this->makeJob($source_id, $target_id))->failed(new \RuntimeException('boom'));

        $this->assertNull($this->findUpload($target_id));
        $this->assertNotNull($this->findUpload($source_id));
        $disk = Storage::disk('local');
        $this->assertSame('private-bytes', $disk->get($source_private));
        $this->assertFalse($disk->exists($target_private)); // renamed to DELETED_deck.pptx
    }

    /**
     * A worker that keeps its EntityManager across attempts still holds the clone's upload entity
     * after staff delete it and add a new upload at the same path. The file there is the new
     * upload's, so neither the copy nor the cleanup may touch it.
     */
    public function testHandleLeavesFileAloneWhenTargetDeletedByAnotherProcess(): void
    {
        [$source, $target] = $this->makePair();
        $this->seedSource($source);
        $target_private = $target->getRelativePath(IStorageTypesConstants::PrivateType);
        $target_public = $target->getRelativePath(IStorageTypesConstants::PublicType);
        Storage::disk('local')->put($target_private, 'staff-bytes');
        $this->deleteRowOutsideEntityManager($target->getId());

        $this->runJob($this->makeJob($source->getId(), $target->getId()));

        $disk = Storage::disk('local');
        $this->assertSame('staff-bytes', $disk->get($target_private));
        $this->assertFalse($disk->exists($target_public));
    }

    public function testFailedLeavesFileAloneWhenTargetDeletedByAnotherProcess(): void
    {
        [$source, $target] = $this->makePair();
        $target_private = $target->getRelativePath(IStorageTypesConstants::PrivateType);
        Storage::disk('local')->put($target_private, 'staff-bytes');
        $this->deleteRowOutsideEntityManager($target->getId());

        ($this->makeJob($source->getId(), $target->getId()))->failed(new \RuntimeException('boom'));

        $this->assertSame('staff-bytes', Storage::disk('local')->get($target_private));
    }

    /**
     * Staff delete the clone's upload while its legs are copying: deleteMediaUpload has already run,
     * so the copy itself must mark what it wrote deleted, or the files stay readable with no record.
     */
    public function testHandleMarksCopiedFilesDeletedWhenTargetDeletedDuringCopy(): void
    {
        [$source, $target] = $this->makePair();
        $this->seedSource($source);
        $source_id = $source->getId();
        $target_id = $target->getId();
        $target_private = $target->getRelativePath(IStorageTypesConstants::PrivateType);
        $target_public = $target->getRelativePath(IStorageTypesConstants::PublicType);

        $real = App::make(IPresentationMediaUploadRepository::class);
        $repository = \Mockery::mock(IPresentationMediaUploadRepository::class);
        $repository->shouldReceive('getByIdFresh')->andReturnUsing(function ($id, $exclusive_lock = false) use ($real, $source_id, $target_id) {
            $found = $real->getByIdFresh($id, $exclusive_lock);
            // the source is the last record read before the legs are copied: delete the clone's
            // upload here, after the copy has seen it and before it re-checks
            if ($id === $source_id) $this->deleteRowOutsideEntityManager($target_id);
            return $found;
        });
        App::instance(IPresentationMediaUploadRepository::class, $repository);
        // the service singleton takes its repositories at construction; drop any instance already
        // resolved so the job gets one built with the wrapped repository
        App::forgetInstance(ISummitService::class);

        $this->runJob($this->makeJob($source_id, $target_id));

        $disk = Storage::disk('local');
        $this->assertFalse($disk->exists($target_private));
        $this->assertFalse($disk->exists($target_public));
        $this->assertSame('private-bytes', $disk->get(dirname($target_private) . '/DELETED_deck.pptx'));
        $this->assertSame('public-bytes', $disk->get(dirname($target_public) . '/DELETED_deck.pptx'));
    }

    public function testFailedDoesNotDeleteReplacedUpload(): void
    {
        [$source, $target] = $this->makePair();
        $target->setFilename('replaced.pptx');
        self::$em->flush();
        $target_id = $target->getId();

        ($this->makeJob($source->getId(), $target_id))->failed(new \RuntimeException('boom'));

        $this->assertNotNull($this->findUpload($target_id));
    }

    public function testFailedDoesNotDeleteSameFilenameReupload(): void
    {
        [$source, $target] = $this->makePair();
        $this->markReuploaded($target);
        $target_private = $target->getRelativePath(IStorageTypesConstants::PrivateType);
        Storage::disk('local')->put($target_private, 'staff-bytes');
        $target_id = $target->getId();

        ($this->makeJob($source->getId(), $target_id))->failed(new \RuntimeException('boom'));

        $this->assertNotNull($this->findUpload($target_id));
        $this->assertSame('staff-bytes', Storage::disk('local')->get($target_private));
    }

    public function testHandleThrowsWhenTargetStorageLocked(): void
    {
        [$source, $target] = $this->makePair();
        $this->seedSource($source);

        $this->withStorageLockHeld($target, function () use ($source, $target) {
            try {
                $this->runJob($this->makeJob($source->getId(), $target->getId()));
                $this->fail('expected UnacquiredLockException');
            } catch (UnacquiredLockException $ex) {
                $this->assertFalse(Storage::disk('local')->exists($target->getRelativePath(IStorageTypesConstants::PrivateType)));
            }
        });
    }

    /**
     * summit-admin re-uploads by delete-then-add, so the processor's row belongs to a NEW upload
     * record at the same storage path. The lock is keyed on that location, so the copy still waits.
     */
    public function testHandleThrowsWhenSameLocationLockedByDifferentUpload(): void
    {
        [$source, $target] = $this->makePair();
        $this->seedSource($source);

        $locks = App::make(ILockManagerService::class);
        // the name a processor writing a DIFFERENT upload record at the clone's deck.pptx would hold
        $name = SummitService::mediaUploadStorageLockName(self::$clonePresentation->getId(), 'deck.pptx');
        $token = $locks->acquireLock($name, 60);
        try {
            $this->runJob($this->makeJob($source->getId(), $target->getId()));
            $this->fail('expected UnacquiredLockException');
        } catch (UnacquiredLockException $ex) {
            $disk = Storage::disk('local');
            $this->assertFalse($disk->exists($target->getRelativePath(IStorageTypesConstants::PrivateType)));
            $this->assertFalse($disk->exists($target->getRelativePath(IStorageTypesConstants::PublicType)));
        } finally {
            $locks->releaseLock($name, $token);
        }
    }

    public function testFailedKeepsUploadWhenTargetStorageLocked(): void
    {
        [$source, $target] = $this->makePair();
        $target_id = $target->getId();

        Log::spy();
        $this->withStorageLockHeld($target, function () use ($source, $target_id) {
            ($this->makeJob($source->getId(), $target_id))->failed(new \RuntimeException('boom'));
        });

        $this->assertNotNull($this->findUpload($target_id));
        // a lock miss can also be Redis being down, so the log names no cause, only the operator action
        Log::shouldHaveReceived('error')->withArgs(fn ($message) => is_string($message)
            && str_contains($message, sprintf('could not take the storage lock to remove clone media upload %s', $target_id))
            && str_contains($message, 'delete the upload or re-upload it on the clone'));
    }

    /**
     * Simulates a final-attempt timeout: Laravel's Worker::registerTimeoutHandler calls failed()
     * from the SIGALRM handler in the SAME process while handle() is suspended mid-copy still
     * holding the target's storage lock (its finally never runs before the process is killed).
     */
    public function testFailedAfterTimeoutRemovesCloneUploadUnderItsOwnLock(): void
    {
        [$source, $target] = $this->makePair();
        $this->seedSource($source);
        $source_private = $source->getRelativePath(IStorageTypesConstants::PrivateType);
        $target_private = $target->getRelativePath(IStorageTypesConstants::PrivateType);
        Storage::disk('local')->put($target_private, 'partial');
        $source_id = $source->getId();
        $target_id = $target->getId();

        $lock_name = SummitService::mediaUploadStorageLockName(self::$clonePresentation->getId(), 'deck.pptx');
        $locks = App::make(ILockManagerService::class);
        $token = $locks->acquireLock($lock_name, 60);

        $held = new \ReflectionProperty(SummitService::class, 'held_media_upload_lock_tokens');
        $held->setAccessible(true);
        $held->setValue(null, [$lock_name => [[$lock_name, $token]]]);

        try {
            ($this->makeJob($source_id, $target_id))
                ->failed(new \Illuminate\Queue\TimeoutExceededException('timed out'));
        } finally {
            $held->setValue(null, []);
        }

        $this->assertNull($this->findUpload($target_id));
        $this->assertNotNull($this->findUpload($source_id));
        $disk = Storage::disk('local');
        $this->assertSame('private-bytes', $disk->get($source_private));
        $this->assertFalse($disk->exists($target_private)); // renamed to DELETED_deck.pptx

        // the lock must have been released by failed(), not left held: a fresh acquire succeeds
        $fresh_token = $locks->acquireLock($lock_name, 60);
        $locks->releaseLock($lock_name, $fresh_token);
    }

    public function testHandleThrowsWhenSourceReuploadInFlight(): void
    {
        [$source, $target] = $this->makePair();
        $this->seedSource($source);
        $this->markReuploaded($source, PendingMediaUpload::STATUS_PROCESSING);

        try {
            $this->runJob($this->makeJob($source->getId(), $target->getId()));
            $this->fail('expected RuntimeException');
        } catch (\RuntimeException $ex) {
            $this->assertStringContainsString('in flight', $ex->getMessage());
            $this->assertFalse(Storage::disk('local')->exists($target->getRelativePath(IStorageTypesConstants::PrivateType)));
        }
    }

    public function testHandleCopiesWhenSourceReuploadCompleted(): void
    {
        [$source, $target] = $this->makePair();
        $this->seedSource($source);
        $this->markReuploaded($source, PendingMediaUpload::STATUS_COMPLETED);

        $this->runJob($this->makeJob($source->getId(), $target->getId()));

        $this->assertSame('private-bytes', Storage::disk('local')->get($target->getRelativePath(IStorageTypesConstants::PrivateType)));
    }

    /**
     * The original's location is locked while its processor writes it, and while another copy
     * fills it when the original is itself a clone. Reading it then could copy a missing or
     * half-written leg, so the copy waits instead and gives the target's lock back.
     */
    public function testHandleThrowsWhenSourceStorageLocked(): void
    {
        [$source, $target] = $this->makePair();
        $this->seedSource($source);

        $this->withStorageLockHeld($source, function () use ($source, $target) {
            try {
                $this->runJob($this->makeJob($source->getId(), $target->getId()));
                $this->fail('expected UnacquiredLockException');
            } catch (UnacquiredLockException $ex) {
                $disk = Storage::disk('local');
                $this->assertFalse($disk->exists($target->getRelativePath(IStorageTypesConstants::PrivateType)));
                $this->assertFalse($disk->exists($target->getRelativePath(IStorageTypesConstants::PublicType)));
            }
        });

        // the target's lock, taken first, was released when the source's could not be taken
        $locks = App::make(ILockManagerService::class);
        $target_lock = SummitService::mediaUploadStorageLockName(self::$clonePresentation->getId(), 'deck.pptx');
        $locks->releaseLock($target_lock, $locks->acquireLock($target_lock, 60));
    }

    public function testHandleReleasesBothLocksAfterCopy(): void
    {
        [$source, $target] = $this->makePair();
        $this->seedSource($source);

        $this->runJob($this->makeJob($source->getId(), $target->getId()));

        $locks = App::make(ILockManagerService::class);
        foreach ([self::$original, self::$clonePresentation] as $presentation) {
            $name = SummitService::mediaUploadStorageLockName($presentation->getId(), 'deck.pptx');
            // acquireLock throws when the copy left the name locked
            $token = $locks->acquireLock($name, 60);
            $this->assertNotEmpty($token);
            $locks->releaseLock($name, $token);
        }
    }

    /**
     * A re-upload of the original under a new name leaves the cloned file in storage beside the new
     * one. The clone's record still says deck.pptx, so it must get deck.pptx's bytes.
     */
    public function testHandleCopiesTheClonedFileAfterSourceReuploadedUnderNewName(): void
    {
        [$source, $target] = $this->makePair();
        $this->seedSource($source);
        $source->setFilename('final.pdf');
        self::$em->flush();
        $disk = Storage::disk('local');
        $disk->put($source->getRelativePath(IStorageTypesConstants::PrivateType), 'new-private-bytes');
        $disk->put($source->getRelativePath(IStorageTypesConstants::PublicType), 'new-public-bytes');
        $this->markReuploaded($source, PendingMediaUpload::STATUS_COMPLETED);

        $this->runJob($this->makeJob($source->getId(), $target->getId()));

        $this->assertSame('deck.pptx', $target->getFilename());
        $this->assertSame('private-bytes', $disk->get($target->getRelativePath(IStorageTypesConstants::PrivateType)));
        $this->assertSame('public-bytes', $disk->get($target->getRelativePath(IStorageTypesConstants::PublicType)));
    }
}
