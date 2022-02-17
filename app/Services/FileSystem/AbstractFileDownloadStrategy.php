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
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
/**
 * Class AbstractFileDownloadStrategy
 * @package App\Services\FileSystem
 */
abstract class AbstractFileDownloadStrategy implements IFileDownloadStrategy
{
    abstract protected function getDriver():string;

    /**
     * @inheritDoc
     */
    public function download(string $path, string $name, array $options = [])
    {
        return Storage::disk($this->getDriver())->download(
            $path,
            $name,
            $options
        );
    }

    /**
     * @param string $path
     * @return string
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public function get(string $path):string
    {
        return Storage::disk($this->getDriver())->get(
            $path,
        );
    }

    /**
     * @param string $path
     * @return resource|null
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public function readStream(string $path){
        return Storage::disk($this->getDriver())->readStream($path);
    }

    /**
     * @param string $relativeFileName
     * @param bool $useTemporaryUrl
     * @param int $ttl
     * @param false $avoidCache
     * @return string|null
     */
    public function getUrl(string $relativeFileName, bool $useTemporaryUrl = false, int $ttl = 10, bool $avoidCache = false): ?string
    {
        Log::debug
        (
            sprintf
            (
                "AbstractFileDownloadStrategy::getUrl relativeFileName %s useTemporaryUrl %b ttl %s avoidCache %b",
                $relativeFileName,
                $useTemporaryUrl,
                $ttl,
                $avoidCache
            )
        );

        $key = sprintf("%s/%s_%b_%s", $this->getDriver(), $relativeFileName, $useTemporaryUrl, $ttl);
        $res = Cache::get($key);
        if(!empty($res) && !$avoidCache) return $res;
        // @see https://laravel.com/docs/8.x/filesystem#temporary-urls
        $res = $useTemporaryUrl ? Storage::disk($this->getDriver())->temporaryUrl($relativeFileName, now()->addMinutes($ttl))
            : Storage::disk($this->getDriver())->url($relativeFileName);
        if(!empty($res)) {
            $ttl = $useTemporaryUrl ? ($ttl - 1) : intval(Config::get('cache_api_response.file_url_lifetime', 3600));
            Log::debug(sprintf("AbstractFileDownloadStrategy::getUrl adding key %s res %s ttl %s to cache", $key, $res, $ttl));
            Cache::add($key, $res, $ttl);
        }
        return $res;
    }

    /**
     * @inheritDoc
     */
    public function delete(string $relativeFileName)
    {
        return Storage::disk($this->getDriver())->delete($relativeFileName);
    }
}