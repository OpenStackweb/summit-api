<?php

namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;

// Summit Attendee Badges

#[OA\Schema(
    schema: 'SummitAttendeeBadge',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'print_date', type: 'integer', nullable: true, example: 1633024800, description: 'Unix timestamp of when the badge was printed'),
        new OA\Property(property: 'qr_code', type: 'string', nullable: true, example: 'QR123456789'),
        new OA\Property(property: 'is_void', type: 'boolean', example: false, description: 'Whether the badge has been voided'),
        new OA\Property(property: 'printed_times', type: 'integer', example: 2, description: 'Number of times this badge has been printed'),
        new OA\Property(property: 'ticket_id', type: 'integer', example: 123, description: 'Associated ticket ID'),
        new OA\Property(property: 'ticket', type: 'Ticket'),
        new OA\Property(property: 'type_id', type: 'integer', example: 5, description: 'Badge type ID'),
        new OA\Property(property: 'type', type: 'BadgeType'),
        new OA\Property(property: 'print_excerpt', type: 'string', example: 'John Doe - Speaker', description: 'Short text excerpt for printing'),
        new OA\Property(
            property: 'features',
            type: 'array',
            description: 'Array of feature IDs assigned to this badge (use expand=features for full details)',
            items: new OA\Items(type: 'integer'),
            example: [1, 2, 3]
        ),
    ],
)]
class SummitAttendeeBadgeSchema
{
}

#[OA\Schema(
    schema: 'PaginatedSummitAttendeeBadgesResponse',
    allOf: [
        new OA\Schema(ref: '#/components/schemas/PaginateDataSchemaResponse'),
        new OA\Schema(
            type: 'object',
            properties: [
                new OA\Property(
                    property: 'data',
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/SummitAttendeeBadge')
                )
            ]
        )
    ]
)]
class PaginatedSummitAttendeeBadgesResponseSchema {}

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
