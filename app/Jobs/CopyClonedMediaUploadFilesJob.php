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

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use services\model\ISummitService;

/**
 * Copies a cloned media upload's files from the original's storage paths to the clone's.
 * SummitService::cloneEvent copies the record only; the clone's path is built from the clone's
 * presentation ID, where nothing exists until this job runs. If every try fails, failed()
 * removes the clone's record so it never points at a missing file.
 * See SummitService::copyClonedMediaUploadFiles and SummitService::removeUncopiedClonedMediaUpload.
 * @package App\Jobs
 */
final class CopyClonedMediaUploadFilesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public $tries = 5;

    public $backoff = [30, 60, 120, 300];

    // strictly below every queue connection's retry_after (1800, config/queue.php) and the
    // database-fallback worker's --timeout, so a slow attempt is never re-served concurrently;
    // ISummitService::MEDIA_UPLOAD_STORAGE_LOCK_LIFETIME stays above it
    public $timeout = 1200;

    public function __construct(
        public readonly int $source_media_upload_id,
        public readonly int $target_media_upload_id,
        public readonly string $filename
    ) {}

    public function handle(ISummitService $service): void
    {
        Log::debug(sprintf("CopyClonedMediaUploadFilesJob::handle source %s target %s", $this->source_media_upload_id, $this->target_media_upload_id));

        $service->copyClonedMediaUploadFiles($this->source_media_upload_id, $this->target_media_upload_id, $this->filename);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error(sprintf(
            "CopyClonedMediaUploadFilesJob::failed source %s target %s: %s",
            $this->source_media_upload_id, $this->target_media_upload_id, $exception->getMessage()
        ));
        Log::error($exception);

        try {
            App::make(ISummitService::class)->removeUncopiedClonedMediaUpload($this->target_media_upload_id, $this->filename, $exception);
        } catch (\Throwable $ex) {
            Log::error(sprintf("CopyClonedMediaUploadFilesJob::failed could not remove clone media upload %s", $this->target_media_upload_id));
            Log::error($ex);
        }
    }
}
