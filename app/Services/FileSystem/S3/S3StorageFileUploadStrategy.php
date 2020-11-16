<?php namespace App\Services\FileSystem\S3;
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
use App\Services\FileSystem\IFileUploadStrategy;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Class S3StorageFileUploadStrategy
 * @package App\Services\FileSystem\S3
 */
final class S3StorageFileUploadStrategy  implements IFileUploadStrategy
{

    /**
     * @inheritDoc
     */
    public function save(UploadedFile $file, string $path, string $filename)
    {
        return Storage::disk('s3')->putFileAs($path, $file, $filename);
    }
}