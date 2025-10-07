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
use Illuminate\Http\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use OpenApi\Attributes as OA;
use Pion\Laravel\ChunkUpload\Exceptions\UploadMissingFileException;
use Pion\Laravel\ChunkUpload\Handler\AbstractHandler;
use Pion\Laravel\ChunkUpload\Receiver\FileReceiver;
/**
 * Class OAuth2ChunkedFilesApiController
 * @package App\Http\Controllers
 */
class OAuth2ChunkedFilesApiController extends UploadController
{
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
    #[OA\Post(
        path: "/api/public/v1/files/upload",
        description: "Upload files using chunked upload mechanism. Supports large file uploads by splitting them into smaller chunks. The endpoint handles both complete uploads and chunked progress updates.",
        summary: 'Upload file with chunked upload support',
        operationId: 'uploadChunkedFile',
        tags: ['Files'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['file'],
                    properties: [
                        new OA\Property(
                            property: 'file',
                            type: 'string',
                            format: 'binary',
                            description: 'File to upload (can be a chunk of a larger file)'
                        ),
                        new OA\Property(
                            property: 'resumableChunkNumber',
                            type: 'integer',
                            description: 'Current chunk number (for resumable.js library)',
                            example: 1
                        ),
                        new OA\Property(
                            property: 'resumableTotalChunks',
                            type: 'integer',
                            description: 'Total number of chunks (for resumable.js library)',
                            example: 5
                        ),
                        new OA\Property(
                            property: 'resumableIdentifier',
                            type: 'string',
                            description: 'Unique identifier for the file upload session (for resumable.js library)',
                            example: '12345-myfile-jpg'
                        ),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success - Upload in progress (chunk uploaded)',
                content: new OA\JsonContent(ref: '#/components/schemas/ChunkedFileUploadProgressResponse')
            ),
            new OA\Response(
                response: 201,
                description: 'Success - Upload complete (all chunks received)',
                content: new OA\JsonContent(ref: '#/components/schemas/ChunkedFileUploadCompleteResponse')
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: "Bad Request - Invalid file or missing parameters"),
            new OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: "Unprocessable Entity - Upload missing file exception"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error - Upload failed")
        ]
    )]
    public function uploadFile(FileReceiver $receiver)
    {
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
            "done" => $done
        ]);
    }

}
