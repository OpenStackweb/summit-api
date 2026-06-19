<?php namespace App\Http\Utils;
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
use App\Services\FileSystem\FileNameSanitizer;
use Illuminate\Http\Request as LaravelRequest;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use models\exceptions\ValidationException;
use Illuminate\Http\UploadedFile;
/**
 * Class FileUploadInfo
 * @package App\Http\Utils
 */
final class FileUploadInfo
{

    /**
     * @var int // in bytes
     */
    private $size;
    /**
     * @var UploadedFile
     */
    private $file;
    /**
     * @var string
     */
    private $fileName;
    /**
     * @var string
     */
    private $fileExt;

    /**
     * @var string
     */
    private $filePath;

    private $md5;

    private $mime_type;

    private $source_bucket;

    /**
     * @param $file
     * @param $fileName
     * @param $fileExt
     * @param $filePath
     * @param $size
     * @param $md5
     * @param $mime_type
     * @param $source_bucket
     */
    private function __construct(
        $file,
        $fileName,
        $fileExt,
        $filePath,
        $size,
        $md5 = null,
        $mime_type = null,
        $source_bucket = null,

    )
    {
        $this->file = $file;
        $this->fileName = $fileName;
        $this->fileExt = $fileExt;
        $this->size = $size;
        $this->filePath = $filePath;
        $this->md5 = $md5;
        $this->mime_type = $mime_type;
        $this->source_bucket = $source_bucket;
    }

    public static function getStorageDriver():string{
        return Config::get("file_upload.storage_driver");
    }

    public static function isLocal():bool{
        return self::getStorageDriver() == "local";
    }

    /**
     * @param LaravelRequest $request
     * @param array $payload
     * @return FileUploadInfo|null
     * @throws ValidationException
     */
    public static function build(LaravelRequest $request, array $payload):?FileUploadInfo {

        $file = null;
        $fileName = null;
        $fileExt = null;
        $size = 0;
        $filePath = null;

        if($request->hasFile('file')) {
            Log::debug(sprintf("FileUploadInfo::build build file is present on request ( MULTIFORM )"));
            $file = $request->file('file');
            // get in bytes should be converted to KB
            $size = $file->getSize();
            if($size == 0)
                throw new ValidationException("File size is zero.");
            $filePath = $file->path();
            $fileName = $file->getClientOriginalName();
            $fileExt = pathinfo($fileName, PATHINFO_EXTENSION);
        }

        if(is_null($file)){
            return self::buildFromPayload($payload);
        }

        $fileName = $fileName && !empty($fileName) ? FileNameSanitizer::sanitize($fileName) : $fileName;

        if(empty($filePath)) return null;

        return new self($file, $fileName, $fileExt, $filePath, $size);
    }

    public static function buildFromPayload(array $payload):?FileUploadInfo{
        $filepath = null;
        $filename = null;
        $md5 = null;
        $size = 0;
        $mime_type = null;
        $source_bucket = null;
        $fileExt = null;
        $file = null;
        if(isset($payload['filepath']) && !empty($payload['filepath'])){
            $filepath = trim($payload['filepath']);
            Log::debug(sprintf("FileUploadInfo::buildFromPayload file is present on as %s storage (%s)", self::getStorageDriver(), $filepath));
            $disk = Storage::disk(self::getStorageDriver());

            if(!$disk->exists($filepath)) {
                Log::warning(sprintf("FileUploadInfo::buildFromPayload file %s is not present at storage (%s)", self::getStorageDriver(), $filepath));
                throw new ValidationException(sprintf("file provide on filepath %s does not exists on %s storage.", self::getStorageDriver(), $filepath));
            }

            // Always retrieve the actual size from storage; never trust client-provided value.
            $size = $disk->size($filepath);

            Log::debug(sprintf("FileUploadInfo::buildFromPayload file %s storage (%s) size %s", self::getStorageDriver(), $filepath, $size));
            if($size == 0) {
                Log::warning(sprintf("FileUploadInfo::buildFromPayload file %s size is zero", $filepath));
                throw new ValidationException("File size is zero.");
            }


            $filename = isset($payload['filename']) ? trim($payload['filename']): pathinfo($filepath, PATHINFO_BASENAME);
            $md5 = isset($payload['md5']) ? trim($payload['md5']) : null;
            $mime_type= isset($payload['mime_type']) ? trim($payload['mime_type']) : null;
            $source_bucket =  isset($payload['source_bucket']) ? trim($payload['source_bucket']) : null;
            $fileExt = pathinfo($filename, PATHINFO_EXTENSION);
            $file = self::isLocal() ? new UploadedFile($disk->path($filepath), $filename): null;
        }

        $filename = $filename && !empty($filename) ? FileNameSanitizer::sanitize($filename) : $filename;

        if(empty($filename) || empty($filepath)) return null;

        return new self(null, $filename, $fileExt, $filepath, $size, $md5, $mime_type, $source_bucket);
    }

    /**
     * @param string $unit
     * @return int|null
     */
    public function getSize(string $unit = FileSizeUtil::Kb): ?int
    {
        if($unit === FileSizeUtil::Kb){
            return $this->size / 1024;
        }

        return $this->size;
    }


    /**
     * @return UploadedFile
     */
    public function getFile(): ?UploadedFile
    {
        return $this->file;
    }

    /**
     * @return string
     */
    public function getFileName(): ?string
    {
        return $this->fileName;
    }

    /**
     * @return string
     */
    public function getFileExt(): ?string
    {
        return $this->fileExt;
    }

    public function delete(){
        if(is_null($this->file)) return;
        $realPath = $this->file->getRealPath();
        $disk = Storage::disk(self::getStorageDriver());
        $disk->delete($realPath);
        Log::debug(sprintf("FileUploadInfo::delete deleting file %s", $realPath));
    }

    public function getMd5(): ?string
    {
        return $this->md5;
    }

    public function getMimeType(): ?string
    {
        return $this->mime_type;
    }

    public function getSourceBucket(): ?string
    {
        return $this->source_bucket;
    }

    public function getFilePath(): ?string
    {
        return $this->filePath;
    }



}
