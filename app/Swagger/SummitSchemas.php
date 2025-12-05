<?php

namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;

// Summit Media Upload Type Schemas


#[OA\Schema(
    schema: "PaginatedSummitMediaUploadTypesResponse",
    description: "Paginated response for Summit Media Upload Types",
    properties: [
        new OA\Property(property: "total", type: "integer", example: 100),
        new OA\Property(property: "per_page", type: "integer", example: 15),
        new OA\Property(property: "current_page", type: "integer", example: 1),
        new OA\Property(property: "last_page", type: "integer", example: 7),
        new OA\Property(
            property: "data",
            type: "array",
            items: new OA\Items(ref: "#/components/schemas/SummitMediaUploadType")
        ),
    ],
    type: "object"
)]
class PaginatedSummitMediaUploadTypesResponseSchema
{
}

#[OA\Schema(
    schema: "SummitMediaUploadTypeCreateRequest",
    description: "Request to create a Summit Media Upload Type",
    required: ["name", "is_mandatory", "max_size", "private_storage_type", "public_storage_type", "type_id", "is_editable"],
    properties: [
        new OA\Property(property: "name", type: "string", maxLength: 255, example: "Speaker Photo"),
        new OA\Property(property: "description", type: "string", maxLength: 5120, nullable: true, example: "High resolution photo of the speaker"),
        new OA\Property(property: "is_mandatory", type: "boolean", example: true),
        new OA\Property(property: "max_size", type: "integer", description: "Maximum file size in KB (must be megabyte aligned)", example: 10240),
        new OA\Property(property: "private_storage_type", type: "string", enum: ["local", "swift", "s3"], example: "local"),
        new OA\Property(property: "public_storage_type", type: "string", enum: ["local", "swift", "s3"], example: "s3"),
        new OA\Property(property: "type_id", type: "integer", example: 456),
        new OA\Property(property: "is_editable", type: "boolean", example: true),
        new OA\Property(property: "use_temporary_links_on_public_storage", type: "boolean", nullable: true, example: false),
        new OA\Property(property: "temporary_links_public_storage_ttl", type: "integer", description: "TTL in seconds (required if use_temporary_links_on_public_storage is true)", nullable: true, example: 3600),
        new OA\Property(property: "min_uploads_qty", type: "integer", minimum: 0, nullable: true, example: 1),
        new OA\Property(property: "max_uploads_qty", type: "integer", minimum: 0, nullable: true, example: 1),
        new OA\Property(
            property: "presentation_types",
            type: "array",
            items: new OA\Items(type: "integer"),
            description: "Array of presentation type IDs",
            nullable: true,
            example: [1, 2, 3]
        ),
    ],
    type: "object"
)]
class SummitMediaUploadTypeCreateRequestSchema
{
}

#[OA\Schema(
    schema: "SummitMediaUploadTypeUpdateRequest",
    description: "Request to update a Summit Media Upload Type",
    properties: [
        new OA\Property(property: "name", type: "string", maxLength: 255, nullable: true, example: "Speaker Photo"),
        new OA\Property(property: "description", type: "string", maxLength: 5120, nullable: true, example: "High resolution photo of the speaker"),
        new OA\Property(property: "is_mandatory", type: "boolean", nullable: true, example: true),
        new OA\Property(property: "max_size", type: "integer", description: "Maximum file size in KB (must be megabyte aligned)", nullable: true, example: 10240),
        new OA\Property(property: "private_storage_type", type: "string", enum: ["local", "swift", "s3"], nullable: true, example: "local"),
        new OA\Property(property: "public_storage_type", type: "string", enum: ["local", "swift", "s3"], nullable: true, example: "s3"),
        new OA\Property(property: "type_id", type: "integer", nullable: true, example: 456),
        new OA\Property(property: "is_editable", type: "boolean", nullable: true, example: true),
        new OA\Property(property: "use_temporary_links_on_public_storage", type: "boolean", nullable: true, example: false),
        new OA\Property(property: "temporary_links_public_storage_ttl", type: "integer", description: "TTL in seconds (required if use_temporary_links_on_public_storage is true)", nullable: true, example: 3600),
        new OA\Property(property: "min_uploads_qty", type: "integer", minimum: 0, nullable: true, example: 1),
        new OA\Property(property: "max_uploads_qty", type: "integer", minimum: 0, nullable: true, example: 1),
        new OA\Property(
            property: "presentation_types",
            type: "array",
            items: new OA\Items(type: "integer"),
            description: "Array of presentation type IDs",
            nullable: true,
            example: [1, 2, 3]
        ),
    ],
    type: "object"
)]
class SummitMediaUploadTypeUpdateRequestSchema
{
}


#[OA\Schema(
    schema: 'PaginatedSummitMediaFileTypesResponse',
    type: 'object',
    allOf: [
        new OA\Schema(ref: '#/components/schemas/PaginateDataSchemaResponse'),
        new OA\Schema(
            properties: [
                new OA\Property(
                    property: 'data',
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/SummitMediaFileType')
                )
            ]
        )
    ]
)]
class PaginatedSummitMediaFileTypesResponseSchema {}

#[OA\Schema(
    schema: 'SummitMediaFileTypeCreateRequest',
    required: ['name', 'allowed_extensions'],
    type: 'object',
    properties: [
        new OA\Property(property: 'name', type: 'string', maxLength: 255, example: 'Presentation'),
        new OA\Property(property: 'description', type: 'string', maxLength: 255, example: 'Presentation files for events'),
        new OA\Property(
            property: 'allowed_extensions',
            type: 'array',
            items: new OA\Items(type: 'string'),
            example: ['pdf', 'ppt', 'pptx'],
            description: 'Array of allowed file extensions'
        ),
    ]
)]
class SummitMediaFileTypeCreateRequestSchema {}

#[OA\Schema(
    schema: 'SummitMediaFileTypeUpdateRequest',
    required: ['allowed_extensions'],
    type: 'object',
    properties: [
        new OA\Property(property: 'name', type: 'string', maxLength: 255, example: 'Presentation'),
        new OA\Property(property: 'description', type: 'string', maxLength: 255, example: 'Presentation files for events'),
        new OA\Property(
            property: 'allowed_extensions',
            type: 'array',
            items: new OA\Items(type: 'string'),
            example: ['pdf', 'ppt', 'pptx'],
            description: 'Array of allowed file extensions'
        ),
    ]
)]
class SummitMediaFileTypeUpdateRequestSchema {}
