<?php namespace libs\utils;
/*
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

use App\Http\Utils\FileUploadInfo;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use models\exceptions\ValidationException;

const LocalChunkSize = 1024;

/**
 * Trait FileUtils
 */
trait FileUtils{

    public static function getLocalTmpStorage():string{
        return '/tmp';
    }

    /**
     * @param string $file_name
     * @param string $remotePath
     * @return string
     * @throws ValidationException
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public static function getFileFromRemoteStorageOnTempStorage(string $file_name, string $remotePath): string{

        $disk = Storage::disk(FileUploadInfo::getStorageDriver());

        if (!$disk->exists($remotePath))
            throw new ValidationException(sprintf("File provide on filepath %s does not exists on %s storage.", FileUploadInfo::getStorageDriver(), $remotePath));

        $stream = $disk->readStream($remotePath);

        if (!is_resource($stream)) {
            throw new ValidationException(sprintf("File provide on filepath %s does not exists on %s storage.", FileUploadInfo::getStorageDriver(), $remotePath));
        }

        //process file per chunks and copy to local
        $localPath = self::getLocalTmpStorage(). "/" . $file_name;
        $out = fopen($localPath, 'w');
        while ($chunk = fread($stream, LocalChunkSize)) {
            fwrite($out, $chunk);
        }
        fclose($stream);
        fclose($out);

        return $localPath;
    }

    /**
     * @param string $localPath
     * @param string $remotePath
     * @return void
     */
    public static function cleanLocalAndRemoteFile(string $localPath, string $remotePath):void{
        $disk = Storage::disk(FileUploadInfo::getStorageDriver());
        Log::debug(sprintf("cleanLocalFile deleting original file %s from storage %s", $remotePath, FileUploadInfo::getStorageDriver()));
        $disk->delete($remotePath);
        Log::debug(sprintf("cleanLocalFile deleting local file %s", $localPath));
        unlink($localPath);
    }
}