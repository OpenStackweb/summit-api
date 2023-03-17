<?php namespace App\Jobs;
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

use App\Services\Filesystem\FileUploadStrategyFactory;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use models\summit\ISummitRepository;

/**
 * Class ProcessMediaUpload
 * @package App\Jobs
 */
class ProcessMediaUpload implements ShouldQueue
{
    const LocalChunkSize = 1024; // bytes

    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 2;

    // no timeout
    public $timeout = 0;
    /*
     * @var int
     */
    public $summit_id;
    /*
     * @var int
     */
    public $media_upload_type_id;
    /*
     * @var string
     */
    public $public_path;
    /*
     * @var string
     */
    public $private_path;
    /*
     * @var string
     */
    public $file_name;
    /*
     * @var string
     */
    public $path;

    /**
     * @param int $summit_id
     * @param int $media_upload_type_id
     * @param string|null $public_path
     * @param string|null $private_path
     * @param string $file_name
     * @param string $path
     */
    public function __construct
    (
        int     $summit_id,
        int     $media_upload_type_id,
        ?string $public_path,
        ?string $private_path,
        string  $file_name,
        string  $path
    )
    {
        $this->summit_id = $summit_id;
        $this->media_upload_type_id = $media_upload_type_id;
        $this->public_path = $public_path;
        $this->private_path = $private_path;
        $this->file_name = $file_name;
        $this->path = $path;
    }

    public static function getStorageDriver(): string
    {
        return Config::get("file_upload.storage_driver");
    }

    public static function isLocal(): bool
    {
        return self::getStorageDriver() == "local";
    }

    public function handle(ISummitRepository $summit_repository)
    {

        try {
            Log::debug(sprintf("ProcessMediaUpload::handle summit id %s media upload type id %s public path %s private path %s file name %s path %s",
                $this->summit_id,
                $this->media_upload_type_id,
                $this->public_path,
                $this->private_path,
                $this->file_name,
                $this->path
            ));

            $summit = $summit_repository->getById($this->summit_id);
            if (is_null($summit)) {
                throw new EntityNotFoundException(sprintf("Summit %s not found.", $this->summit_id));
            }

            $mediaUploadType = $summit->getMediaUploadTypeById($this->media_upload_type_id);
            if (is_null($mediaUploadType)) {
                throw new EntityNotFoundException(sprintf("Media Upload Type %s not found.", $this->media_upload_type_id));
            }

            $disk = Storage::disk(self::getStorageDriver());

            if (!$disk->exists($this->path))
                throw new ValidationException(sprintf("file provide on filepath %s does not exists on %s storage.", self::getStorageDriver(), $this->path));

            $stream = $disk->readStream($this->path);

            if (!is_resource($stream)) {
                throw new ValidationException(sprintf("file provide on filepath %s does not exists on %s storage.", self::getStorageDriver(), $this->path));
            }
            // write to temp folder

            $localPath = "/tmp/" . $this->file_name;
            $out = fopen($localPath, 'w');
            while ($chunk = fread($stream, self::LocalChunkSize)) {
                fwrite($out, $chunk);
            }
            fclose($stream);
            fclose($out);

            $strategy = FileUploadStrategyFactory::build($mediaUploadType->getPrivateStorageType());
            if (!is_null($strategy)) {
                Log::debug(sprintf("ProcessMediaUpload::handle saving file %s to private storage", $this->file_name));
                $strategy->saveFromPath(
                    $localPath,
                    $this->private_path,
                    $this->file_name
                );
            }

            $strategy = FileUploadStrategyFactory::build($mediaUploadType->getPublicStorageType());
            if (!is_null($strategy)) {
                Log::debug(sprintf("ProcessMediaUpload::handle saving file %s to public storage", $this->file_name));
                $options = $mediaUploadType->isUseTemporaryLinksOnPublicStorage() ? [] : 'public';
                $strategy->saveFromPath
                (
                    $localPath,
                    $this->public_path,
                    $this->file_name,
                    $options
                );
            }

            Log::debug(sprintf("ProcessMediaUpload::handle deleting original file %s from storage %s", $this->file_name, self::getStorageDriver()));
            $disk->delete($this->path);
            Log::debug(sprintf("ProcessMediaUpload::handle deleting local file %s", $localPath));
            unlink($localPath);
        } catch (\Exception $ex) {
            Log::error($ex);
            throw $ex;
        }
    }

    public function failed(\Throwable $exception)
    {
        Log::error($exception);
    }

}