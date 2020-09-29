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
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use models\exceptions\ValidationException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
/**
 * Class FileUploadInfo
 * @package App\Http\Utils
 */
final class FileUploadInfo
{
    /**
     * @var int
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
     * FileUploadInfo constructor.
     * @param $file
     * @param $fileName
     * @param $fileExt
     * @param $size
     */
    private function __construct(
        $file,
        $fileName,
        $fileExt,
        $size
    )
    {
        $this->file = $file;
        $this->fileName = $fileName;
        $this->fileExt = $fileExt;
        $this->size = $size;
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

        if($request->hasFile('file')) {
            Log::debug(sprintf("FileUploadInfo::build build file is present on request ( MULTIFORM )"));
            $file = $request->file('file');
            // get in bytes should be converted to KB
            $size = $file->getSize();
            if($size == 0)
                throw new ValidationException("File size is zero.");
            $size = $size/1024; // convert bytes to KB
            $fileName = $file->getClientOriginalName();
            $fileExt = pathinfo($fileName, PATHINFO_EXTENSION);
        }

        if(is_null($file) && isset($payload['filepath'])){
            Log::debug(sprintf("FileUploadInfo::build build file is present on as local storage (%s)", $payload['filepath']));
            $disk = Storage::disk('local');

            if(!$disk->exists($payload['filepath']))
                throw new ValidationException(sprintf("file provide on filepath %s does not exists on local storage.", $payload['filepath']));

            // get in bytes should be converted to KB
            $size = $disk->size($payload['filepath']);
            if($size == 0)
                throw new ValidationException("File size is zero.");

            $size = $size/1024; // convert bytes to KB
            $fileName =  pathinfo($payload['filepath'],PATHINFO_BASENAME);
            $fileExt = pathinfo($fileName, PATHINFO_EXTENSION);
            $file = new UploadedFile($disk->path($payload['filepath']), $fileName);
        }

        if(is_null($file)) return null;

        $fileName = FileNameSanitizer::sanitize($fileName);

        return new self($file, $fileName, $fileExt, $size);
    }

    /**
     * @return int
     */
    public function getSize(): ?int
    {
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
        Log::debug(sprintf("FileUploadInfo::delete deleting file %s", $realPath));
        unlink($realPath);
    }

}