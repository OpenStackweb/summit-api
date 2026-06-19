<?php

namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "FileDTO",
    description: "File metadata payload produced by the File API after a successful upload",
    required: ["filepath", "filename", "md5", "size"],
    properties: [
        new OA\Property(property: "filepath", type: "string", description: "Remote storage path of the uploaded file", example: "companies/1/tmp/logo.png"),
        new OA\Property(property: "filename", type: "string", description: "Original file name", example: "logo.png"),
        new OA\Property(property: "md5", type: "string", description: "MD5 hash of the file for integrity verification", example: "d41d8cd98f00b204e9800998ecf8427e"),
        new OA\Property(property: "size", type: "integer", description: "File size in bytes", example: 204800),
        new OA\Property(property: "mime_type", type: "string", nullable: true, description: "MIME type of the file", example: "image/png"),
        new OA\Property(property: "source_bucket", type: "string", nullable: true, description: "Source storage bucket", example: "my-uploads-bucket"),
    ],
    type: "object"
)]
class FileDTOSchema
{
}
