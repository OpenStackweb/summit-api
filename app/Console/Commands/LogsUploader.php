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

use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

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

    private function formatFileName(string $date_str) {
        return "laravel-{$date_str}.log";
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public function handle()
    {
        try {
            $date_format = 'Y-m-d';
            $ui_file_name = 'uploads_info';
            $pending_uploads = [];
            $yesterday = Carbon::yesterday('UTC');

            $logs_fs = Storage::disk('logs');
            if ($logs_fs->exists($ui_file_name)) {
                $uploads_info = explode(PHP_EOL, $logs_fs->get($ui_file_name));
                sort($uploads_info);
                $first_date_str = $uploads_info[0];
                $date_from = Carbon::createFromFormat($date_format, $first_date_str);
                //get upload gaps
                $period = CarbonPeriod::create($date_from, $yesterday);

                foreach ($period as $date) {
                    $date_str = $date->format($date_format);
                    if (!in_array($date_str, $uploads_info)) {
                        $pending_uploads[] = $this->formatFileName($date_str);
                    }
                }
            } else {
                $pending_uploads[] = $this->formatFileName($yesterday->format($date_format));
            }

            foreach ($pending_uploads as $pending_upload) {
                $logs_fs->append($ui_file_name, $pending_upload);
            }

            //Storage::disk('logs_s3')->put($log_name, $content);
        } catch (Exception $ex) {
            Log::error($ex);
        }
    }
}
