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

    /**
     * @param $file
     * @param $fileName
     * @param $fileExt
     * @param $filePath
     * @param $size
     */
    private function __construct(
        $file,
        $fileName,
        $fileExt,
        $filePath,
        $size
    )
    {
        $this->file = $file;
        $this->fileName = $fileName;
        $this->fileExt = $fileExt;
        $this->size = $size;
        $this->filePath = $filePath;
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

        if(is_null($file) && isset($payload['filepath'])){
            Log::debug(sprintf("FileUploadInfo::build build file is present on as %s storage (%s)", self::getStorageDriver(), $payload['filepath']));
            $disk = Storage::disk(self::getStorageDriver());

            if(!$disk->exists($payload['filepath']))
                throw new ValidationException(sprintf("file provide on filepath %s does not exists on %s storage.", self::getStorageDriver(), $payload['filepath']));

            // get in bytes should be converted to KB
            $size = $disk->size($payload['filepath']);
            Log::debug(sprintf("FileUploadInfo::build build file %s storage (%s) size %s", self::getStorageDriver(), $payload['filepath'], $size));
            if($size == 0)
                throw new ValidationException("File size is zero.");
            $filePath = $payload['filepath'];
            $fileName = pathinfo($payload['filepath'],PATHINFO_BASENAME);
            $fileExt = pathinfo($fileName, PATHINFO_EXTENSION);
            $file = new UploadedFile($disk->path($payload['filepath']), $fileName);
        }

        if(is_null($file)) return null;

        $fileName = FileNameSanitizer::sanitize($fileName);

        return new self($file, $fileName, $fileExt, $filePath, $size);
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

    public function getFilePath(): ?string
    {
        return $this->filePath;
    }

}