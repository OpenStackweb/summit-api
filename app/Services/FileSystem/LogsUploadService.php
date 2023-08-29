<?php namespace App\Services\FileSystem;

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
use Exception;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
/**
 * Class LogsUploadService
 * @package App\Services\Model
 */
final class LogsUploadService
    implements ILogsUploadService
{
    /**
     * Returns the log file name based on the date parameter.
     */
    private function formatFileName(Carbon $date): string
    {
        $app_log_file_name_prefix = Config::get("log.app_log_file_name_prefix");
        $date_format = 'Y-m-d';
        return "{$app_log_file_name_prefix}-{$date->format($date_format)}.log";
    }

    /**
     * Returns the remote log file path based on the local name and the node number.
     */
    private function formatRemoteLogFilePath(string $local_name, string $node_number): string
    {
        return "{$node_number}/{$local_name}.gz";
    }

    /**
     * @inheritdoc
     */
    public function startUpload(): void
    {
        try {
            $node_number = Config::get("server.app_node_number");
            $remote_storage_name = Config::get("log.remote_storage_name");

            $today_log_file_name = $this->formatFileName(Carbon::today('UTC'));

            $logs_fs = Storage::disk('logs');
            $remote_logs_fs = Storage::disk($remote_storage_name);

            $local_log_files = array_filter($logs_fs->allFiles(), function ($file_name) use ($today_log_file_name) {
                return $file_name != $today_log_file_name && str_ends_with($file_name, '.log');
            });
            sort($local_log_files);

            foreach ($local_log_files as $local_log_file) {
                $content = $logs_fs->get($local_log_file);
                if (empty($content)) continue;

                $remote_file_path = $this->formatRemoteLogFilePath($local_log_file, $node_number);
                if ($remote_logs_fs->exists($remote_file_path)) continue;

                $deflateContext = deflate_init(ZLIB_ENCODING_GZIP);
                $compressed = deflate_add($deflateContext, $content, ZLIB_FINISH);

                $remote_logs_fs->put($remote_file_path, $compressed);

                $logs_fs->put("{$local_log_file}.gz", $compressed);
                $logs_fs->delete($local_log_file);
            }
        } catch (Exception $ex) {
            Log::error($ex);
        }
    }
}