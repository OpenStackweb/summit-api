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
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Pion\Laravel\ChunkUpload\Exceptions\UploadMissingFileException;
use Pion\Laravel\ChunkUpload\Handler\AbstractHandler;
use Pion\Laravel\ChunkUpload\Receiver\FileReceiver;
/**
 * Class OAuth2ChunkedFilesApiController
 * @package App\Http\Controllers
 */
class OAuth2ChunkedFilesApiController extends UploadController {
  /**
   * Handles the file upload
   *
   * @param FileReceiver $receiver
   *
   * @return JsonResponse
   *
   * @throws UploadMissingFileException
   *
   */
  public function uploadFile(FileReceiver $receiver) {
    // check if the upload is success, throw exception or return response you need
    if ($receiver->isUploaded() === false) {
      throw new UploadMissingFileException();
    }
    // receive the file
    $save = $receiver->receive();

    // check if the upload has finished (in chunk mode it will send smaller files)
    if ($save->isFinished()) {
      // save the file and return any response you need
      return $this->saveFile($save->getFile());
    }

    // we are in chunk mode, lets send the current progress
    /** @var AbstractHandler $handler */
    $handler = $save->handler();
    $done = $handler->getPercentageDone();
    return response()->json([
      "done" => $done,
    ]);
  }
}
