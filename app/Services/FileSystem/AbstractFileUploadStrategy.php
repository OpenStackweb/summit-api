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
    abstract protected function getDriver():string;
    /**
     * @inheritDoc
     */
    public function save(UploadedFile $file, string $path, string $filename)
    {
        return Storage::disk($this->getDriver())->putFileAs($path, $file, $filename);
    }

    /**
     * @param string $path
     * @param string $filename
     * @return bool|mixed
     */
    public function markAsDeleted(string $path, string $filename)
    {
        Log::debug(sprintf("AbstractFileUploadStrategy:: markAsDeleted path %s filename %s", $path, $filename));
        return Storage::disk($this->getDriver())->move
        (
            sprintf("%s/%s", $path, $filename),
            sprintf("%s/DELETED_%s", $path, $filename)
        );
    }
}