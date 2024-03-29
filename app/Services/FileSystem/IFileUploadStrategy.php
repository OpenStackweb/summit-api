<?php namespace App\Services\FileSystem;
/**
 * Copyright 2020 OpenStack Foundation
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

/**
 * Interface IFileUploadStrategy
 * @package App\Services\FileSystem
 */
interface IFileUploadStrategy
{
    /**
     * @return string
     */
    public function getDriver():string;

    /**
     * @param UploadedFile $file
     * @param string $path
     * @param string $filename
     * @param mixed $options
     * @return false|string
     */
    public function save(UploadedFile $file, string $path, string $filename, $options = []);

    /**
     * @param string $path
     * @param string|null $filename
     * @return mixed
     */
    public function markAsDeleted(string $path, ?string $filename = null);

    /**
     * @param resource $fp
     * @param string $path
     * @param mixed $options
     * @return bool
     */
    public function saveFromStream($fp, string $path, $options = []):bool;

    /**
     * @param string $file
     * @param string $path
     * @param string $filename
     * @param mixed $options
     * @return false|string
     */
    public function saveFromPath(string $file, string $path, string $filename, $options = []):bool;
}