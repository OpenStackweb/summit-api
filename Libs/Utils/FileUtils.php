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
     */
    public static function getFileFromRemoteStorageOnTempStorage(string $file_name, string $remotePath): string
    {
        Log::debug(sprintf("FileUtils::getFileFromRemoteStorageOnTempStorage %s %s", $file_name, $remotePath));

        $disk = Storage::disk(FileUploadInfo::getStorageDriver());

        if (!$disk->exists($remotePath)) {
            throw new ValidationException(sprintf(
                "File provided on filepath %s does not exist on %s storage.",
                $remotePath,
                FileUploadInfo::getStorageDriver()
            ));
        }

        $stream = $disk->readStream($remotePath);
        if (!is_resource($stream)) {
            throw new ValidationException(sprintf(
                "File provided on filepath %s does not exist on %s storage.",
                $remotePath,
                FileUploadInfo::getStorageDriver()
            ));
        }

        // Make reads/writes fully blocking and unbuffered to avoid short/empty reads on network streams
        stream_set_blocking($stream, true);
        @stream_set_read_buffer($stream, 0);

        $localPath = rtrim(self::getLocalTmpStorage(), '/')."/".$file_name;

        $out = fopen($localPath, 'wb'); // binary mode
        if ($out === false) {
            fclose($stream);
            throw new ValidationException("Unable to open local temp file for writing: {$localPath}");
        }
        stream_set_blocking($out, true);
        @stream_set_write_buffer($out, 0);

        // Prefer native stream copy (handles partial reads correctly)
        $bytes = @stream_copy_to_stream($stream, $out);
        if ($bytes === false) {
            // Robust fallback loop in case stream_copy_to_stream is disabled
            $bytes = 0;
            $bufSize = 1024 * 1024; // 1MB
            while (!feof($stream)) {
                $chunk = fread($stream, $bufSize);
                if ($chunk === false) {
                    fclose($stream);
                    fclose($out);
                    @unlink($localPath);
                    throw new ValidationException("Read error from remote stream.");
                }
                if ($chunk !== '') {
                    $written = fwrite($out, $chunk);
                    if ($written === false) {
                        fclose($stream);
                        fclose($out);
                        @unlink($localPath);
                        throw new ValidationException("Write error to local file.");
                    }
                    $bytes += $written;
                }
            }
        }

        fflush($out);
        // fsync not available in PHP; fflush is best-effort
        fclose($stream);
        fclose($out);

        // Post-copy validation: size must match remote object size (when available)
        try {
            $remoteSize = $disk->size($remotePath);          // Flysystem reports size in bytes
            $localSize  = @filesize($localPath);
            if (is_int($remoteSize) && is_int($localSize) && $remoteSize !== $localSize) {
                @unlink($localPath);
                throw new ValidationException(
                    "Size mismatch copying {$remotePath}: remote={$remoteSize} local={$localSize}"
                );
            }
        } catch (\Throwable $e) {
          throw new ValidationException($e->getMessage());
        }

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