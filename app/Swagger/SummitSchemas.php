<?php

namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'SummitScheduleConfig',
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
        new OA\Property(
            property: 'type', 
            type: 'string', 
            enum: ['DATE', 'TRACK', 'TRACK_GROUPS', 'COMPANY', 'LEVEL', 'SPEAKERS', 'VENUES', 'EVENT_TYPES', 'TITLE', 'CUSTOM_ORDER', 'ABSTRACT', 'TAGS'], 
            example: 'DATE'
        ),
        new OA\Property(property: 'is_enabled', type: 'boolean', example: true),
        new OA\Property(property: 'label', type: 'string', example: 'Date'),
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

//
