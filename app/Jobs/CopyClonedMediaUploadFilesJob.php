<?php namespace App\Jobs;
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
use App\Models\Foundation\Summit\Repositories\IPresentationMediaUploadRepository;
use App\Models\Utils\IStorageTypesConstants;
use App\Services\Filesystem\FileDownloadStrategyFactory;
use App\Services\Filesystem\FileUploadStrategyFactory;
use App\Services\Utils\Exceptions\UnacquiredLockException;
use App\Services\Utils\ILockManagerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use libs\utils\ITransactionService;
use models\summit\PresentationMediaUpload;

/**
 * Copies a cloned media upload's files from the original's storage paths to the clone's.
 * SummitService::cloneEvent copies the record only; the clone's path is built from the clone's
 * presentation ID, where nothing exists until this job runs. If every try fails, failed()
 * deletes the clone's record so it never points at a missing file.
 *
 * A clone that staff have re-uploaded since is never written to or deleted. updateMediaUploadFrom
 * keeps the record's ID and may keep its filename, so the signal is the PendingMediaUpload row
 * every re-upload creates (a clone never has one). The check and the writes run under the same
 * per-upload storage lock SummitService::processPendingMediaUploads takes, so a re-upload that
 * lands mid-copy is written after this job, never overwritten by it.
 * @package App\Jobs
 */
final class CopyClonedMediaUploadFilesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    // above $timeout, so a killed attempt's lock expires on its own before anything waits on it long
    const STORAGE_LOCK_LIFETIME = 1300;

    public $tries = 5;

    public $backoff = [30, 60, 120, 300];

    // strictly below every queue connection's retry_after (1800, config/queue.php) and the
    // database-fallback worker's --timeout, so a slow attempt is never re-served concurrently
    public $timeout = 1200;

    // Keyed by lock name, set for the duration handle() holds that lock. On a final-attempt
    // timeout, Laravel's Worker::registerTimeoutHandler calls failed() from the SIGALRM handler
    // in the SAME process while handle() is suspended mid-copy still holding the lock - its
    // finally never runs before the process is killed. failed() runs on a freshly unserialized
    // job instance, so an instance property can't carry the token across to it; a static can,
    // since it lives in the same process. See failed() for how this is consumed.
    private static array $held_lock_tokens = [];

    public function __construct(
        public int $source_media_upload_id,
        public int $target_media_upload_id,
        public string $filename
    ) {}

    public static function storageLockName(int $media_upload_id): string
    {
        return sprintf('media_upload_storage.%s', $media_upload_id);
    }

    public function handle(
        IPresentationMediaUploadRepository $repository,
        IPendingMediaUploadRepository $pending_repository,
        ILockManagerService $lock_service,
        ITransactionService $tx_service
    ): void
    {
        Log::debug(sprintf("CopyClonedMediaUploadFilesJob::handle source %s target %s", $this->source_media_upload_id, $this->target_media_upload_id));

        $lock_name = self::storageLockName($this->target_media_upload_id);
        // UnacquiredLockException propagates: the processor is writing this upload, so retry later
        $token = $lock_service->acquireLock($lock_name, self::STORAGE_LOCK_LIFETIME);
        self::$held_lock_tokens[$lock_name] = $token;
        try {
            $legs = $this->resolveLegs($repository, $pending_repository, $tx_service);
            foreach ($legs as $leg) {
                $this->copyLeg($leg['storage'], $leg['from'], $leg['to'], $leg['options']);
            }
        } finally {
            unset(self::$held_lock_tokens[$lock_name]);
            $lock_service->releaseLock($lock_name, $token);
        }
    }

    /**
     * Resolved inside a short read transaction, under the lock; the copy runs outside the transaction.
     */
    private function resolveLegs(
        IPresentationMediaUploadRepository $repository,
        IPendingMediaUploadRepository $pending_repository,
        ITransactionService $tx_service
    ): array
    {
        return $tx_service->transaction(function () use ($repository, $pending_repository) {
            $target = $repository->getById($this->target_media_upload_id);
            if (!$target instanceof PresentationMediaUpload) {
                Log::info(sprintf("CopyClonedMediaUploadFilesJob::handle target %s gone, nothing to copy", $this->target_media_upload_id));
                return [];
            }
            if ($this->isReplaced($target, $pending_repository)) {
                Log::info(sprintf("CopyClonedMediaUploadFilesJob::handle target %s was re-uploaded, nothing to copy", $this->target_media_upload_id));
                return [];
            }
            $source = $repository->getById($this->source_media_upload_id);
            // the clone still needs this file; fail so the retries, then failed(), resolve it
            if (!$source instanceof PresentationMediaUpload)
                throw new \RuntimeException(sprintf("CopyClonedMediaUploadFilesJob source media upload %s is gone", $this->source_media_upload_id));
            // a same-filename re-upload of the original leaves the old file in place until the
            // processor writes the new one; wait for it rather than copy stale bytes
            if ($pending_repository->hasInFlightForMediaUpload($source->getId()))
                throw new \RuntimeException(sprintf("CopyClonedMediaUploadFilesJob source media upload %s has a re-upload in flight", $this->source_media_upload_id));

            $type = $target->getMediaUploadType();
            if (is_null($type)) return [];

            return [
                [
                    'storage' => $type->getPrivateStorageType(),
                    'from' => $source->getRelativePath(IStorageTypesConstants::PrivateType),
                    'to' => $target->getRelativePath(IStorageTypesConstants::PrivateType),
                    'options' => [],
                ],
                [
                    'storage' => $type->getPublicStorageType(),
                    'from' => $source->getRelativePath(IStorageTypesConstants::PublicType),
                    'to' => $target->getRelativePath(IStorageTypesConstants::PublicType),
                    'options' => $type->isUseTemporaryLinksOnPublicStorage() ? [] : 'public',
                ],
            ];
        });
    }

    private function isReplaced(PresentationMediaUpload $target, IPendingMediaUploadRepository $pending_repository): bool
    {
        return $target->getFilename() !== $this->filename
            || $pending_repository->hasAnyForMediaUpload($target->getId());
    }

    private function copyLeg(?string $storage, string $from, string $to, $options): void
    {
        if (empty($storage)) return;
        $download = FileDownloadStrategyFactory::build($storage);
        $upload = FileUploadStrategyFactory::build($storage);
        if (is_null($download) || is_null($upload)) return; // None

        // Always overwrite: a killed attempt can leave a truncated file at $to (the Local adapter
        // writes straight to the final path), so existence never proves a leg finished.
        $stream = $download->readStream($from);
        if (!is_resource($stream))
            throw new \RuntimeException(sprintf("CopyClonedMediaUploadFilesJob source missing on %s: %s", $storage, $from));

        try {
            if (!$upload->saveFromStream($stream, $to, $options))
                throw new \RuntimeException(sprintf("CopyClonedMediaUploadFilesJob could not write on %s: %s", $storage, $to));
        } finally {
            if (is_resource($stream)) fclose($stream);
        }
        Log::info(sprintf("CopyClonedMediaUploadFilesJob::copyLeg %s copied %s -> %s", $storage, $from, $to));
    }

    public function failed(\Throwable $exception): void
    {
        Log::error(sprintf(
            "CopyClonedMediaUploadFilesJob::failed source %s target %s: %s",
            $this->source_media_upload_id, $this->target_media_upload_id, $exception->getMessage()
        ));

        $lock_service = App::make(ILockManagerService::class);
        $lock_name = self::storageLockName($this->target_media_upload_id);

        if (array_key_exists($lock_name, self::$held_lock_tokens)) {
            // A final-attempt timeout calls failed() from the worker's SIGALRM handler in the
            // SAME process, with handle() suspended mid-copy still holding this lock - its finally
            // never runs before the process is killed. The lock is still ours: re-acquiring it
            // here would always throw UnacquiredLockException, so clean up directly and release
            // the token we already hold instead.
            $token = self::$held_lock_tokens[$lock_name];
            Log::error(sprintf("CopyClonedMediaUploadFilesJob::failed target %s copy timed out while still holding its own storage lock, removing the clone's upload", $this->target_media_upload_id));
            try {
                $this->removeTarget();
            } catch (\Throwable $ex) {
                // the job is already terminal: this error log is the operator's signal that the clone
                // still has an upload with no file behind it (fix: delete it or re-upload on the clone)
                Log::error(sprintf("CopyClonedMediaUploadFilesJob::failed could not remove clone media upload %s", $this->target_media_upload_id));
                Log::error($ex);
            } finally {
                unset(self::$held_lock_tokens[$lock_name]);
                $lock_service->releaseLock($lock_name, $token);
            }
            return;
        }

        try {
            $lock_service->lock(
                $lock_name,
                fn () => $this->removeTarget(),
                self::STORAGE_LOCK_LIFETIME
            );
        } catch (UnacquiredLockException $ex) {
            // the lock is held by another process; a same-process timeout is handled above, so
            // this can only be the processor writing a re-upload of this clone - keep the record
            Log::warning(sprintf("CopyClonedMediaUploadFilesJob::failed target %s storage is locked by a re-upload, keeping it", $this->target_media_upload_id));
        } catch (\Throwable $ex) {
            // the job is already terminal: this error log is the operator's signal that the clone
            // still has an upload with no file behind it (fix: delete it or re-upload on the clone)
            Log::error(sprintf("CopyClonedMediaUploadFilesJob::failed could not remove clone media upload %s", $this->target_media_upload_id));
            Log::error($ex);
        }
    }

    private function removeTarget(): void
    {
        $repository = App::make(IPresentationMediaUploadRepository::class);
        $pending_repository = App::make(IPendingMediaUploadRepository::class);

        // remove the record first and capture its target-only paths; storage is touched only
        // after commit, so a failed commit can never leave a record whose file was moved
        $cleanup = App::make(ITransactionService::class)->transaction(function () use ($repository, $pending_repository) {
            $target = $repository->getById($this->target_media_upload_id);
            if (!$target instanceof PresentationMediaUpload) return [];
            if ($this->isReplaced($target, $pending_repository)) {
                Log::info(sprintf("CopyClonedMediaUploadFilesJob::failed target %s was re-uploaded, keeping it", $this->target_media_upload_id));
                return [];
            }

            $paths = [];
            $type = $target->getMediaUploadType();
            if (!is_null($type)) {
                foreach ([
                    IStorageTypesConstants::PrivateType => $type->getPrivateStorageType(),
                    IStorageTypesConstants::PublicType => $type->getPublicStorageType(),
                ] as $kind => $storage) {
                    if (!empty($storage))
                        $paths[] = [$storage, $target->getPath($kind), $target->getFilename()];
                }
            }

            $presentation = $target->getPresentation();
            if (!is_null($presentation)) $presentation->removeMediaUpload($target);
            Log::warning(sprintf("CopyClonedMediaUploadFilesJob::failed removed clone media upload %s", $this->target_media_upload_id));
            return $paths;
        });

        // same rename-to-DELETED_ cleanup as PresentationService::deleteMediaUpload
        foreach ($cleanup as [$storage, $path, $filename]) {
            try {
                $strategy = FileUploadStrategyFactory::build($storage);
                if (!is_null($strategy)) $strategy->markAsDeleted($path, $filename);
            } catch (\Throwable $ex) {
                Log::error(sprintf("CopyClonedMediaUploadFilesJob::failed could not mark %s/%s deleted on %s", $path, $filename, $storage));
                Log::error($ex);
            }
        }
    }
}
