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

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use services\model\IPresentationService;

/**
 * Class ProcessMediaUpload
 * @package App\Jobs
 */
class ProcessMediaUpload implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 2;

    // no timeout
    public $timeout = 0;
    /*
     * @var int
     */
    public $summit_id;
    /*
     * @var int
     */
    public $media_upload_type_id;
    /*
     * @var string
     */
    public $public_path;
    /*
     * @var string
     */
    public $private_path;
    /*
     * @var string
     */
    public $file_name;
    /*
     * @var string
     */
    public $path;

    /**
     * @param int $summit_id
     * @param int $media_upload_type_id
     * @param string|null $public_path
     * @param string|null $private_path
     * @param string $file_name
     * @param string $path
     */
    public function __construct
    (
        int     $summit_id,
        int     $media_upload_type_id,
        ?string $public_path,
        ?string $private_path,
        string  $file_name,
        string  $path
    )
    {
        $this->summit_id = $summit_id;
        $this->media_upload_type_id = $media_upload_type_id;
        $this->public_path = $public_path;
        $this->private_path = $private_path;
        $this->file_name = $file_name;
        $this->path = $path;
    }

    public function handle(IPresentationService $service)
    {

        try {
            Log::debug(sprintf("ProcessMediaUpload::handle summit id %s media upload type id %s public path %s private path %s file name %s path %s",
                $this->summit_id,
                $this->media_upload_type_id,
                $this->public_path,
                $this->private_path,
                $this->file_name,
                $this->path
            ));

            $service->processMediaUpload(
                $this->summit_id,
                $this->media_upload_type_id,
                $this->public_path,
                $this->private_path,
                $this->file_name,
                $this->path
            );

        } catch (\Exception $ex) {
            Log::error($ex);
            throw $ex;
        }
    }

    public function failed(\Throwable $exception)
    {
        Log::error($exception);
    }

}