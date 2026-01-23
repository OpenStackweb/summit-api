<?php

namespace App\Swagger\schemas;

use models\summit\ISponsorshipTypeConstants;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'SummitScheduleConfigContent',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'created', type: 'integer', description: 'Unix timestamp', example: 1640995200),
        new OA\Property(property: 'last_edited', type: 'integer', description: 'Unix timestamp', example: 1640995200),
        new OA\Property(property: 'key', type: 'string', example: 'schedule-main'),
        new OA\Property(property: 'summit_id', type: 'integer', example: 1),
        new OA\Property(property: 'is_my_schedule', type: 'boolean', example: false),
        new OA\Property(property: 'only_events_with_attendee_access', type: 'boolean', example: false),
        new OA\Property(property: 'color_source', type: 'string', enum: ['EVENT_TYPES', 'TRACK', 'TRACK_GROUP'], example: 'EVENT_TYPES'),
        new OA\Property(property: 'is_enabled', type: 'boolean', example: true),
        new OA\Property(property: 'is_default', type: 'boolean', example: true),
        new OA\Property(property: 'hide_past_events_with_show_always_on_schedule', type: 'boolean', example: false),
        new OA\Property(property: 'time_format', type: 'string', enum: ['12h', '24h'], example: '12h'),
        new OA\Property(
            property: 'filters',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/SummitScheduleFilterElementConfig')
        ),
        new OA\Property(
            property: 'pre_filters',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/SummitSchedulePreFilterElementConfig')
        )
    ]
)]
class SummitScheduleConfigContentSchema {}


#[OA\Schema(
    schema: 'SummitScheduleConfig',
    type: 'object',
    properties: [
        new OA\Property(
            property: '<type>',
            description: 'Dynamic property name with SummitScheduleFilterElementConfig->type as key',
            type: 'object',
            allOf: [
                new OA\Schema(ref: '#/components/schemas/SummitScheduleConfigContent')
            ]
        )
    ]
)]
class SummitScheduleConfigSchema {}

#[OA\Schema(
    schema: 'PaginatedSummitScheduleConfigsResponse',
    allOf: [
        new OA\Schema(ref: '#/components/schemas/PaginateDataSchemaResponse'),
        new OA\Schema(
            type: 'object',
            properties: [
                new OA\Property(
                    property: 'data',
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/SummitScheduleConfig')
                )
            ]
        )
    ]
)]
class PaginatedSummitScheduleConfigsResponseSchema {}

#[OA\Schema(
    schema: 'SummitScheduleFilterElementConfig',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'created', type: 'integer', description: 'Unix timestamp', example: 1640995200),
        new OA\Property(property: 'last_edited', type: 'integer', description: 'Unix timestamp', example: 1640995200),
        new OA\Property(property: 'label', type: 'string', example: 'Date'),
        new OA\Property(property: 'is_enabled', type: 'boolean', example: true),
        new OA\Property(property: 'order', type: 'integer', example: 1),
        new OA\Property(
            property: 'type',
            type: 'string',
            enum: ['DATE', 'TRACK', 'TRACK_GROUPS', 'COMPANY', 'LEVEL', 'SPEAKERS', 'VENUES', 'EVENT_TYPES', 'TITLE', 'CUSTOM_ORDER', 'ABSTRACT', 'TAGS'],
            example: 'DATE'
        ),
    ]
)]
class SummitScheduleFilterElementConfigSchema {}

#[OA\Schema(
    schema: 'SummitSchedulePreFilterElementConfig',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'created', type: 'integer', description: 'Unix timestamp', example: 1640995200),
        new OA\Property(property: 'last_edited', type: 'integer', description: 'Unix timestamp', example: 1640995200),
        new OA\Property(
            property: 'type',
            type: 'string',
            enum: ['DATE', 'TRACK', 'TRACK_GROUPS', 'COMPANY', 'LEVEL', 'SPEAKERS', 'VENUES', 'EVENT_TYPES', 'TITLE', 'CUSTOM_ORDER', 'ABSTRACT', 'TAGS'],
            example: 'TAGS'
        ),
        new OA\Property(
            property: 'values',
            type: 'array',
            items: new OA\Items(type: 'string'),
            example: ['tag1', 'tag2']
        )
    ]
)]
class SummitSchedulePreFilterElementConfigSchema {}

#[OA\Schema(
    schema: 'SummitScheduleConfigCreateRequest',
    type: 'object',
    required: ['key'],
    properties: [
        new OA\Property(property: 'key', type: 'string', example: 'schedule-main'),
        new OA\Property(property: 'is_my_schedule', type: 'boolean', example: false),
        new OA\Property(property: 'only_events_with_attendee_access', type: 'boolean', example: false),
        new OA\Property(property: 'color_source', type: 'string', enum: ['EVENT_TYPES', 'TRACK', 'TRACK_GROUP'], example: 'EVENT_TYPES'),
        new OA\Property(property: 'is_enabled', type: 'boolean', example: true),
        new OA\Property(property: 'is_default', type: 'boolean', example: true),
        new OA\Property(property: 'hide_past_events_with_show_always_on_schedule', type: 'boolean', example: false),
        new OA\Property(property: 'time_format', type: 'string', enum: ['12h', '24h'], example: '12h'),
        new OA\Property(
            property: 'filters',
            type: 'array',
            items: new OA\Items(
                type: 'object',
                properties: [
                    new OA\Property(property: 'type', type: 'string', enum: ['DATE', 'TRACK', 'TRACK_GROUPS', 'COMPANY', 'LEVEL', 'SPEAKERS', 'VENUES', 'EVENT_TYPES', 'TITLE', 'CUSTOM_ORDER', 'ABSTRACT', 'TAGS']),
                    new OA\Property(property: 'is_enabled', type: 'boolean'),
                    new OA\Property(property: 'label', type: 'string', nullable: true)
                ]
            ),
            nullable: true
        ),
        new OA\Property(
            property: 'pre_filters',
            type: 'array',
            items: new OA\Items(
                type: 'object',
                properties: [
                    new OA\Property(property: 'type', type: 'string', enum: ['DATE', 'TRACK', 'TRACK_GROUPS', 'COMPANY', 'LEVEL', 'SPEAKERS', 'VENUES', 'EVENT_TYPES', 'TITLE', 'CUSTOM_ORDER', 'ABSTRACT', 'TAGS']),
                    new OA\Property(property: 'values', type: 'array', items: new OA\Items(type: 'string'))
                ]
            ),
            nullable: true
        )
    ]
)]
class SummitScheduleConfigCreateRequestSchema {}

#[OA\Schema(
    schema: 'SummitScheduleConfigUpdateRequest',
    type: 'object',
    properties: [
        new OA\Property(property: 'key', type: 'string', example: 'schedule-main', nullable: true),
        new OA\Property(property: 'is_my_schedule', type: 'boolean', example: false, nullable: true),
        new OA\Property(property: 'only_events_with_attendee_access', type: 'boolean', example: false, nullable: true),
        new OA\Property(property: 'color_source', type: 'string', enum: ['EVENT_TYPES', 'TRACK', 'TRACK_GROUP'], example: 'EVENT_TYPES', nullable: true),
        new OA\Property(property: 'is_enabled', type: 'boolean', example: true, nullable: true),
        new OA\Property(property: 'is_default', type: 'boolean', example: true, nullable: true),
        new OA\Property(property: 'hide_past_events_with_show_always_on_schedule', type: 'boolean', example: false, nullable: true),
        new OA\Property(property: 'time_format', type: 'string', enum: ['12h', '24h'], example: '12h', nullable: true),
        new OA\Property(
            property: 'filters',
            type: 'array',
            items: new OA\Items(
                type: 'object',
                properties: [
                    new OA\Property(property: 'type', type: 'string', enum: ['DATE', 'TRACK', 'TRACK_GROUPS', 'COMPANY', 'LEVEL', 'SPEAKERS', 'VENUES', 'EVENT_TYPES', 'TITLE', 'CUSTOM_ORDER', 'ABSTRACT', 'TAGS']),
                    new OA\Property(property: 'is_enabled', type: 'boolean'),
                    new OA\Property(property: 'label', type: 'string', nullable: true)
                ]
            ),
            nullable: true
        ),
        new OA\Property(
            property: 'pre_filters',
            type: 'array',
            items: new OA\Items(
                type: 'object',
                properties: [
                    new OA\Property(property: 'type', type: 'string', enum: ['DATE', 'TRACK', 'TRACK_GROUPS', 'COMPANY', 'LEVEL', 'SPEAKERS', 'VENUES', 'EVENT_TYPES', 'TITLE', 'CUSTOM_ORDER', 'ABSTRACT', 'TAGS']),
                    new OA\Property(property: 'values', type: 'array', items: new OA\Items(type: 'string'))
                ]
            ),
            nullable: true
        )
    ]
)]
class SummitScheduleConfigUpdateRequestSchema {}

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
        new OA\Property(property: 'ticket_id', type: 'integer', example: 123, description: 'SummitAttendeeTicket ID, use expand=ticket for full object details'),
        new OA\Property(property: 'type_id', type: 'integer', example: 5, description: 'SummitBadgeType ID, use expand=type for full object details'),
        new OA\Property(property: 'type', ref: '#/components/schemas/SummitBadgeType'),
        new OA\Property(property: 'print_excerpt', type: 'string', example: 'John Doe - Speaker', description: 'Short text excerpt for printing'),
        new OA\Property(
            property: 'features',
            type: 'array',
            description: 'Array of SummitBadgeFeatureType IDs assigned to this badge (use expand=features for full details)',
            items: new OA\Items(oneOf: [
                new OA\Schema(type: 'integer'),
                new OA\Schema(ref: '#/components/schemas/SummitBadgeFeatureType'),
            ]),
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
class PaginatedSummitMediaUploadTypesResponseSchema {}

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
class SummitMediaUploadTypeCreateRequestSchema {}

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
class SummitMediaUploadTypeUpdateRequestSchema {}


#[OA\Schema(
    schema: 'PaginatedSummitSponsorshipTypesResponse',
    allOf: [
        new OA\Schema(ref: '#/components/schemas/PaginateDataSchemaResponse'),
        new OA\Schema(
            type: 'object',
            properties: [
                new OA\Property(
                    property: 'data',
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/SummitSponsorshipType')
                )
            ]
        )
    ]
)]
class PaginatedSummitSponsorshipTypesResponseSchema {}

#[OA\Schema(
    schema: 'SummitSponsorshipTypeCreateRequest',
    type: 'object',
    required: ['name', 'label', 'size'],
    properties: [
        new OA\Property(property: 'name', type: 'string', example: 'platinum'),
        new OA\Property(property: 'label', type: 'string', example: 'Platinum'),
        new OA\Property(property: 'size', type: 'string', example: ISponsorshipTypeConstants::BigSize, enum: ISponsorshipTypeConstants::AllowedSizes),
    ]
)]
class SummitSponsorshipTypeCreateRequestSchema {}

#[OA\Schema(
    schema: 'SummitSponsorshipTypeUpdateRequest',
    type: 'object',
    properties: [
        new OA\Property(property: 'name', type: 'string', example: 'platinum'),
        new OA\Property(property: 'label', type: 'string', example: 'Platinum'),
        new OA\Property(property: 'size', type: 'string', example: ISponsorshipTypeConstants::BigSize, enum: ISponsorshipTypeConstants::AllowedSizes),
        new OA\Property(property: 'order', type: 'integer', example: 1, minimum: 1),
    ]
)]
class SummitSponsorshipTypeUpdateRequestSchema {}


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
