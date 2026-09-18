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
use App\Services\Model\FileInfoDTO;
use Illuminate\Http\UploadedFile;
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

        $localPath = tempnam(self::getLocalTmpStorage(), 'fproc_');
        if ($localPath === false) {
            throw new ValidationException("Unable to create local temporary file.");
        }

        $out = fopen($localPath, 'wb'); // binary mode
        if ($out === false) {
            fclose($stream);
            @unlink($localPath);
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
          throw new ValidationException($e->getMessage(), 0, $e);
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

    /**
     * Downloads a file from remote storage to a local temp path, verifies its MD5 (when provided),
     * invokes $uploader($owner_entity_id, UploadedFile) to persist it, then cleans up. On failure the
     * remote file is preserved so queue retries can re-download it. Cleanup errors after a successful
     * upload are logged but not re-thrown - upload success determines job success, not storage housekeeping.
     * @param FileInfoDTO $file_info_dto
     * @param callable $uploader
     * @return mixed whatever $uploader returns
     * @throws ValidationException
     */
    public static function processFileFromRemoteStorage(FileInfoDTO $file_info_dto, callable $uploader)
    {
        $localPath = self::getFileFromRemoteStorageOnTempStorage(
            $file_info_dto->filename,
            $file_info_dto->filepath
        );
        $succeeded = false;
        try {
            if (!is_null($file_info_dto->md5)) {
                $localHash = md5_file($localPath);
                if ($localHash === false)
                    throw new ValidationException("File integrity check failed: unable to read local temp file.");
                if ($localHash !== strtolower($file_info_dto->md5))
                    throw new ValidationException("File integrity check failed: MD5 mismatch.");
            }
            $file = new UploadedFile(
                path: $localPath,
                originalName: $file_info_dto->filename,
                mimeType: $file_info_dto->mime_type,
                error: null,
                test: true,
            );
            $res = $uploader($file_info_dto->owner_entity_id, $file);
            $succeeded = true;
        } finally {
            if ($succeeded) {
                try {
                    self::cleanLocalAndRemoteFile($localPath, $file_info_dto->filepath);
                } catch (\Throwable $e) {
                    // Upload succeeded; cleanup failure is non-fatal. Log and continue so the
                    // job does not retry and create duplicate File records.
                    Log::warning(sprintf(
                        "FileUtils::processFileFromRemoteStorage cleanup failed after successful upload (entity=%s member=%s filepath=%s): %s",
                        $file_info_dto->owner_entity_class,
                        $file_info_dto->owner_member_name,
                        $file_info_dto->filepath,
                        $e->getMessage()
                    ));
                }
            } else {
                self::cleanLocalFile($localPath);
            }
        }
        return $res;
    }

    /**
     * Deletes only the local temp file. Use in finally blocks where the remote
     * file must be preserved on failure so queue job retries can re-download it.
     * @param string $localPath
     * @return void
     */
    public static function cleanLocalFile(string $localPath): void {
        if (file_exists($localPath)) {
            Log::debug(sprintf("FileUtils::cleanLocalFile deleting local temp file %s", $localPath));
            @unlink($localPath);
        }
    }
}