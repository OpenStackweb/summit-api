<?php

namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;

//

#[OA\Schema(
    schema: 'SummitMediaFileType',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'created', type: 'integer', format: 'int64', example: 1633024800),
        new OA\Property(property: 'last_edited', type: 'integer', format: 'int64', example: 1633024800),
        new OA\Property(property: 'name', type: 'string', example: 'Presentation'),
        new OA\Property(property: 'description', type: 'string', example: 'Presentation files for events'),
        new OA\Property(property: 'is_system_defined', type: 'boolean', example: false),
        new OA\Property(
            property: 'allowed_extensions',
            type: 'array',
            items: new OA\Items(type: 'string'),
            example: ['pdf', 'ppt', 'pptx']
        ),
    ]
)]
class SummitMediaFileTypeSchema {}

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
