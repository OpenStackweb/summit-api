<?php namespace App\Jobs;
/*
 * Copyright 2023 OpenStack Foundation
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

use App\Services\Filesystem\FileUploadStrategyFactory;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Class ProcessClearStorage4MediaUploadsimplements
 * @package App\Jobs
 */
final class ProcessClearStorage4MediaUploads implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 5;

    /**
     * @var array
     */
    public $payload;

    /**
     * @param array $payload
     */
    public function __construct(array $payload)
    {
        Log::debug(sprintf("ProcessClearStorage4MediaUploads::__construct %s", json_encode($payload)));
        $this->payload = $payload;
    }

    public function handle()
    {
        Log::debug(sprintf("ProcessClearStorage4MediaUploads::handle payload %s", json_encode($this->payload)));

        foreach($this->payload as $storageType => $header) {

            $strategy = FileUploadStrategyFactory::build($storageType);

            $files = $header['files'];
            foreach ($files as $file) {
                Log::debug
                (
                    sprintf
                    (
                        "ProcessClearStorage4MediaUploads::handle processing storage type %s file %s",
                        $storageType,
                        $file
                    )
                );
                $file_parts = explode("|", $file);
                $strategy->markAsDeleted($file_parts[0], $file_parts[1]);
            }

            $paths = $header['paths'];
            foreach ($paths as $path) {
                Log::debug
                (
                    sprintf
                    (
                        "ProcessClearStorage4MediaUploads::handle processing storage type %s path %s",
                        $storageType,
                        $path
                    )
                );

                $strategy->markAsDeleted($path);
            }
        }
    }

    public function failed(\Throwable $exception)
    {
        Log::error($exception);
    }
}