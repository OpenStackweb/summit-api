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
use App\Services\Model\FileInfoDTO;
use App\Services\Model\IFilePostProcessorService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class FileProcessingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;

    public $timeout = 600;

    public function backoff(): array {
        return [30, 60];
    }


    public function __construct(
        public readonly FileInfoDTO $fileInfoDTO,
    ) {}

    public function handle(IFilePostProcessorService $service){
        $result = $service->postProcessFileFromFileApi($this->fileInfoDTO);
        if (!$result) {
            throw new \RuntimeException(sprintf("FileProcessingJob: postProcessFileFromFileApi returned false for %s", $this->fileInfoDTO));
        }
    }

    public function failed(\Throwable $exception)
    {
        Log::error(sprintf( "FileProcessingJob::failed %s", $exception->getMessage()));
    }
}
