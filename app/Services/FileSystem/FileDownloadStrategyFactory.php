<?php namespace App\Services\Filesystem;
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
use App\Models\Utils\IStorageTypesConstants;
use App\Services\FileSystem\Dropbox\DropboxStorageFileDownloadStrategy;
use App\Services\FileSystem\Local\LocalStorageFileDownloadStrategy;
use App\Services\FileSystem\Swift\SwiftStorageFileDownloadStrategy;
/**
 * Class FileDownloadStrategyFactory
 * @package App\Services\Filesystem
 */
final class FileDownloadStrategyFactory
{
    /**
     * @param string $storageType
     * @return IFileDownloadStrategy|null
     */
    public static function build(string $storageType):? IFileDownloadStrategy {
        switch ($storageType){
            case IStorageTypesConstants::Local;
                return new LocalStorageFileDownloadStrategy();
                break;
            case IStorageTypesConstants::DropBox;
                return new DropboxStorageFileDownloadStrategy();
                break;
            case IStorageTypesConstants::Swift;
                return new SwiftStorageFileDownloadStrategy();
                break;
        }
        return null;
    }
}
