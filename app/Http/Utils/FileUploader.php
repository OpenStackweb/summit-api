<?php namespace App\Http\Utils;
/**
 * Copyright 2017 OpenStack Foundation
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
use App\Services\Model\IFolderService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use models\exceptions\ValidationException;
use models\main\File;
use models\main\IFolderRepository;
/**
 * Class FileUploader
 * @package App\Http\Utils
 */
final class FileUploader implements IFileUploader
{
    /**
     * @var IFolderService
     */
    private $folder_service;

    /**
     * @var IFolderRepository
     */
    private $folder_repository;

    /**
     * FileUploader constructor.
     * @param IFolderService $folder_service
     * @param IFolderRepository $folder_repository
     */
    public function __construct
    (
        IFolderService $folder_service,
        IFolderRepository $folder_repository
    )
    {
        $this->folder_service    = $folder_service;
        $this->folder_repository = $folder_repository;
    }

    private static $default_replacements = [
        '/\s/' => '-', // remove whitespace
        '/_/' => '-', // underscores to dashes
        '/[^A-Za-z0-9+.\-]+/' => '', // remove non-ASCII chars, only allow alphanumeric plus dash and dot
        '/[\-]{2,}/' => '-', // remove duplicate dashes
        '/^[\.\-_]+/' => '', // Remove all leading dots, dashes or underscores
    ];

    /**
     * @param UploadedFile $file
     * @param string $path
     * @param bool $is_image
     * @return File
     * @throws \Exception
     */
    public function build(UploadedFile $file, string $path, bool $is_image = false){
        $attachment = new File();
        try {

            $client_original_name = $file->getClientOriginalName();
            $ext                  = pathinfo($client_original_name, PATHINFO_EXTENSION);
            $client_original_name = FileNameSanitizer::sanitize($client_original_name);

            $idx = 1;
            $name  = pathinfo($client_original_name, PATHINFO_FILENAME);
            if(empty($name)){
                $client_original_name = uniqid().'.'.$ext;
                $name  = pathinfo($client_original_name, PATHINFO_FILENAME);
            }

            while($this->folder_repository->existByName($client_original_name)){
                $client_original_name = $name.$idx.'.'.$ext;
                ++$idx;
            }

            $title = pathinfo($client_original_name, PATHINFO_FILENAME);

            if(empty($title)){
                throw new ValidationException("empty file name is not valid");
            }

            Log::debug(sprintf("FileUploader::build: folder_name %s client original name %s", $path, $client_original_name));

            $local_path = Storage::disk('public')->putFileAs
            (
                $path,
                $file,
                $client_original_name
            );

            Log::debug(sprintf("FileUploader::build: saved to local path %s", $local_path));

            Log::debug(sprintf("FileUploader::build: invoking folder service findOrMake folder_name %s", $path));
            $folder = $this->folder_service->findOrMake($path);
            $local_path = Storage::disk('public')->path($local_path);
            $attachment->setParent($folder);
            $attachment->setName($client_original_name);
            $file_name = sprintf("assets/%s/%s", $path, $client_original_name);
            Log::debug(sprintf("FileUploader::build file_name %s", $file_name));
            $attachment->setFilename($file_name);
            $title = str_replace(['-','_'],' ', preg_replace('/\.[^.]+$/', '', $title));
            Log::debug(sprintf("FileUploader::build title %s", $title));
            $attachment->setTitle($title);
            $attachment->setShowInSearch(true);
            if ($is_image) // set className
                $attachment->setImage();
            Log::debug(sprintf("FileUploader::build uploading to bucket %s", $local_path));
            // store at cloud
            Storage::disk('assets')->putFileAs
            (
                $path,
                $file,
                $client_original_name
            );
            $attachment->setCloudMeta('LastPut', time());
            $attachment->setCloudStatus('Live');
            $attachment->setCloudSize(filesize($local_path));
        }
        catch(ValidationException $ex){
            Log::warning($ex);
            throw $ex;
        }
        catch (\Exception $ex){
            Log::error($ex);
            throw $ex;
        }
        return $attachment;
    }
}