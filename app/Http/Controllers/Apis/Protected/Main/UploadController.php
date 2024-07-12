<?php namespace App\Http\Controllers;
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
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Config;
use Pion\Laravel\ChunkUpload\Exceptions\UploadFailedException;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Pion\Laravel\ChunkUpload\Exceptions\UploadMissingFileException;
use Pion\Laravel\ChunkUpload\Handler\AbstractHandler;
use Pion\Laravel\ChunkUpload\Handler\HandlerFactory;
use Pion\Laravel\ChunkUpload\Receiver\FileReceiver;
/**
 * Class UploadController
 * @package App\Http\Controllers
 */
class UploadController extends BaseController {
  /**
   * Handles the file upload
   *
   * @param Request $request
   *
   * @return JsonResponse
   *
   * @throws UploadMissingFileException
   * @throws UploadFailedException
   */
  public function upload(Request $request) {
    // create the file receiver
    $receiver = new FileReceiver("file", $request, HandlerFactory::classFromRequest($request));

    // check if the upload is success, throw exception or return response you need
    if ($receiver->isUploaded() === false) {
      throw new UploadMissingFileException();
    }

    // receive the file
    $save = $receiver->receive();

    // check if the upload has finished (in chunk mode it will send smaller files)
    if ($save->isFinished()) {
      // save the file and return any response you need, current example uses `move` function. If you are
      // not using move, you need to manually delete the file by unlink($save->getFile()->getPathname())
      return $this->saveFile($save->getFile());
    }

    // we are in chunk mode, lets send the current progress
    /** @var AbstractHandler $handler */
    $handler = $save->handler();

    return response()->json([
      "done" => $handler->getPercentageDone(),
      "status" => true,
    ]);
  }

  /**
   * Saves the file
   *
   * @param UploadedFile $file
   *
   * @return JsonResponse
   */
  protected function saveFile(UploadedFile $file) {
    $fileName = $this->createFilename($file);
    // Group files by mime type
    $mime = str_replace("/", "-", $file->getMimeType());
    // Group files by the date
    $dateFolder = date("Y-m-d");

    // Build the file path
    $filePath = "upload/{$mime}/{$dateFolder}/";

    $disk = Storage::disk(Config::get("file_upload.storage_driver"));

    $disk->putFileAs($filePath, $file, $fileName);

    unlink($file->getPathname());

    return response()->json([
      "path" => $filePath,
      "name" => $fileName,
      "mime_type" => $mime,
    ]);
  }

  /**
   * Create unique filename for uploaded file
   * @param UploadedFile $file
   * @return string
   */
  protected function createFilename(UploadedFile $file) {
    $extension = $file->getClientOriginalExtension();
    $filename = str_replace("." . $extension, "", $file->getClientOriginalName()); // Filename without extension

    // Add timestamp hash to name of the file
    $filename .= "_" . md5(time()) . "." . $extension;

    return $filename;
  }
}
