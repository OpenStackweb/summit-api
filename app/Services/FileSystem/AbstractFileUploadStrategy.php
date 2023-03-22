<?php namespace App\Services\FileSystem;
/**
 * Copyright 2021 OpenStack Foundation
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
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
/**
 * Class AbstractFileUploadStrategy
 * @package App\Services\FileSystem
 */
abstract class AbstractFileUploadStrategy implements IFileUploadStrategy
{
    /**
     * @param UploadedFile $file
     * @param string $path
     * @param string $filename
     * @param mixed $options
     * @return false|string
     */
    public function save(UploadedFile $file, string $path, string $filename, $options = [])
    {
        Log::debug(sprintf("AbstractFileUploadStrategy::save path %s filename %s options %s", $path, $filename, json_encode($options)));
        return Storage::disk($this->getDriver())->putFileAs($path, $file, $filename, $options);
    }

    /**
     * @param string $file
     * @param string $path
     * @param string $filename
     * @param mixed $options
     * @return false|string
     */
    public function saveFromPath(string $file, string $path, string $filename, $options = []):bool
    {
        Log::debug(sprintf("AbstractFileUploadStrategy::saveFromPath path %s filename %s options %s", $path, $filename, json_encode($options)));
        return Storage::disk($this->getDriver())->putFileAs($path, $file, $filename, $options);
    }

    /**
     * @param resource $fp
     * @param string $path
     * @param mixed $options
     * @return bool
     */
    public function saveFromStream($fp, string $path, $options = []):bool{
        return Storage::disk($this->getDriver())->put($path, $fp,  $options );
    }

    /**
     * @param string $path
     * @param string|null $filename
     * @return bool|mixed
     */
    public function markAsDeleted(string $path, ?string $filename = null)
    {
        Log::debug(sprintf("AbstractFileUploadStrategy:: markAsDeleted path %s filename %s", $path, $filename));
        $from = empty($filename) ? $path : sprintf("%s/%s", $path, $filename);
        $to = null;
        if(empty($filename)){
            $parts = explode("/", $path);
            $parts[count($parts) - 1] = sprintf("DELETED_%s", $parts[count($parts) - 1] );
            $to = implode("/", $parts);
        }
        else{
            $to = sprintf("%s/DELETED_%s", $path, $filename);
        }
        Log::debug(sprintf("AbstractFileUploadStrategy:: markAsDeleted from %s to %s", $from, $to));

        return Storage::disk($this->getDriver())->move
        (
            $from,
            $to
        );
    }
}