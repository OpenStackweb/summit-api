<?php namespace App\Services\FileSystem\Swift;
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
use App\Services\FileSystem\IFileDownloadStrategy;
use Illuminate\Support\Facades\Storage;
/**
 * Class SwiftStorageFileDownloadStrategy
 * @package App\Services\FileSystem\Swift
 */
class SwiftStorageFileDownloadStrategy implements IFileDownloadStrategy
{
    /**
     * @inheritDoc
     */
    public function download(string $path, string $name, array $options = [])
    {
        return Storage::disk('swift')->download(
            $path,
            $name,
            $options
        );
    }

    /**
     * @inheritDoc
     */
    public function getUrl(string $relativeFileName): ?string
    {
        return Storage::disk('swift')->url($relativeFileName);
    }

    /**
     * @inheritDoc
     */
    public function delete(string $relativeFileName)
    {
        return Storage::disk('swift')->delete($relativeFileName);
    }
}