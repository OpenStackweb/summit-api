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
use App\Services\FileSystem\Dropbox\DropboxStorageFileUploadStrategy;
use App\Services\FileSystem\Local\LocalStorageFileUploadStrategy;
use App\Services\FileSystem\S3\S3StorageFileUploadStrategy;
use App\Services\FileSystem\Swift\SwiftStorageFileUploadStrategy;
/**
 * Class FileUploadStrategyFactory
 * @package App\Services\Filesystem
 */
final class FileUploadStrategyFactory {
  /**
   * @param string $storageType
   * @return IFileUploadStrategy|null
   */
  public static function build(string $storageType): ?IFileUploadStrategy {
    switch ($storageType) {
      case IStorageTypesConstants::Local:
        return new LocalStorageFileUploadStrategy();
        break;
      case IStorageTypesConstants::DropBox:
        return new DropboxStorageFileUploadStrategy();
        break;
      case IStorageTypesConstants::Swift:
        return new SwiftStorageFileUploadStrategy();
        break;
      case IStorageTypesConstants::S3:
        return new S3StorageFileUploadStrategy();
        break;
    }
    return null;
  }
}
