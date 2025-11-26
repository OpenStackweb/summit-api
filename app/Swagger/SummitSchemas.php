<?php

namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;

// Summit Documents

#[OA\Schema(
    schema: "SummitDocument",
    description: "Summit document",
    type: "object",
    properties: [
        new OA\Property(property: "id", type: "integer", example: 1),
        new OA\Property(property: "created", type: "integer", description: "Unix timestamp", example: 1640995200),
        new OA\Property(property: "last_edited", type: "integer", description: "Unix timestamp", example: 1640995200),
        new OA\Property(property: "name", type: "string", example: "Code of Conduct"),
        new OA\Property(property: "description", type: "string", example: "Summit code of conduct document"),
        new OA\Property(property: "show_always", type: "boolean", example: true),
        new OA\Property(property: "label", type: "string", example: "Code of Conduct"),
        new OA\Property(property: "file", type: "string", format: "uri", nullable: true, example: "https://example.com/document.pdf"),
        new OA\Property(property: "web_link", type: "string", format: "uri", nullable: true, example: "https://example.com/page"),
        new OA\Property(property: "selection_plan_id", type: "integer", nullable: true, description: "SelectionPlan ID, full object description when ?expand=summit (summit)"),
        new OA\Property(
            property: "event_types",
            type: "array",
            items: new OA\Items(type: "integer"),
            description: "Array of SummitEventType: objects when expanded, ids otherwise",
        ),
        new OA\Property(property: "summit_id", type: "integer", description: "Summit ID, full object description when ?expand=summit (summit)"),
    ]
)]
class SummitDocumentSchema {}

#[OA\Schema(
    schema: "PaginatedSummitDocumentsResponse",
    description: "Paginated list of summit documents",
    allOf: [
        new OA\Schema(ref: "#/components/schemas/PaginateDataSchemaResponse"),
        new OA\Schema(
            properties: [
                new OA\Property(
                    property: "data",
                    type: "array",
                    items: new OA\Items(ref: "#/components/schemas/SummitDocument")
                )
            ]
        )
    ]
)]
class PaginatedSummitDocumentsResponseSchema {}

#[OA\Schema(
    schema: "SummitDocumentCreateRequest",
    description: "Request to create a summit document",
    required: ["name", "label"],
    type: "object",
    properties: [
        new OA\Property(property: "name", type: "string", example: "Code of Conduct"),
        new OA\Property(property: "label", type: "string", example: "Code of Conduct"),
        new OA\Property(property: "description", type: "string", nullable: true, example: "Summit code of conduct document"),
        new OA\Property(property: "show_always", type: "boolean", nullable: true, example: true),
        new OA\Property(property: "web_link", type: "string", format: "uri", nullable: true, example: "https://example.com/page"),
        new OA\Property(property: "selection_plan_id", type: "integer", nullable: true, example: 1),
        new OA\Property(
            property: "event_types",
            type: "array",
            nullable: true,
            items: new OA\Items(type: "integer"),
            example: [1, 2, 3]
        ),
        new OA\Property(
            property: "file",
            type: "string",
            format: "binary",
            nullable: true,
            description: "Document file upload (required if web_link not provided)"
        ),
    ]
)]
class SummitDocumentCreateRequest {}

#[OA\Schema(
    schema: "SummitDocumentUpdateRequest",
    description: "Request to update a summit document",
    type: "object",
    properties: [
        new OA\Property(property: "name", type: "string", nullable: true, example: "Code of Conduct"),
        new OA\Property(property: "label", type: "string", nullable: true, example: "Code of Conduct"),
        new OA\Property(property: "description", type: "string", nullable: true, example: "Summit code of conduct document"),
        new OA\Property(property: "show_always", type: "boolean", nullable: true, example: true),
        new OA\Property(property: "web_link", type: "string", format: "uri", nullable: true, example: "https://example.com/page"),
        new OA\Property(property: "selection_plan_id", type: "integer", nullable: true, example: 1),
        new OA\Property(
            property: "event_types",
            type: "array",
            nullable: true,
            items: new OA\Items(type: "integer"),
            example: [1, 2, 3]
        ),
    ]
)]
class SummitDocumentUpdateRequest {}

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
