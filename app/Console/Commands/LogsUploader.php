<?php namespace App\Console\Commands;
/**
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

use App\Services\FileSystem\ILogsUploadService;
use Illuminate\Console\Command;

/**
 * Class LogsUploader
 * @package App\Console\Commands
 */
final class LogsUploader extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'management:logs-uploader';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'management:logs-uploader';


    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Upload local logs to external storage';

    /**
     * @var ILogsUploadService
     */
    protected $logs_upload_service;

    /**
     * SummitEventSetAvgRateProcessor constructor.
     * @param ILogsUploadService $logs_upload_service
     */
    public function __construct(ILogsUploadService $logs_upload_service)
    {
        parent::__construct();
        $this->logs_upload_service = $logs_upload_service;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->logs_upload_service->startUpload();
    }
}
