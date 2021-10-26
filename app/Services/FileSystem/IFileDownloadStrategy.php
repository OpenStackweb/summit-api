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

/**
 * Interface IFileDownloadStrategy
 * @package App\Services\FileSystem
 */
interface IFileDownloadStrategy
{
    /**
     * @param string $path
     * @param string $name
     * @param array $options
     * @return mixed`
     */
    public function download(string $path, string $name, array $options = []);

    /**
     * @param string $relativeFileName
     * @return string|null
     */
    public function getUrl(string $relativeFileName):?string;

    /**
     * @param string $relativeFileName
     * @return mixed
     */
    public function delete(string $relativeFileName);

    /**
     * @param string $path
     * @return string
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public function get(string $path):string;

    /**
     * @param string $path
     * @return resource|null
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public function readStream(string $path);
}