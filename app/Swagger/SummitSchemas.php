<?php

namespace App\Swagger\schemas;

use models\summit\ISponsorshipTypeConstants;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'SummitSponsorshipType',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'widget_title', type: 'string', example: 'Platinum Sponsors', nullable: true),
        new OA\Property(property: 'lobby_template', type: 'string', example: 'big-header', nullable: true),
        new OA\Property(property: 'expo_hall_template', type: 'string', example: 'big-images', nullable: true),
        new OA\Property(property: 'sponsor_page_template', type: 'string', example: 'big-header', nullable: true),
        new OA\Property(property: 'event_page_template', type: 'string', example: 'big-header', nullable: true),
        new OA\Property(property: 'sponsor_page_use_disqus_widget', type: 'boolean', example: true),
        new OA\Property(property: 'sponsor_page_use_live_event_widget', type: 'boolean', example: true),
        new OA\Property(property: 'sponsor_page_use_schedule_widget', type: 'boolean', example: true),
        new OA\Property(property: 'sponsor_page_use_banner_widget', type: 'boolean', example: true),
        new OA\Property(property: 'badge_image', type: 'string', example: 'https://example.com/badge.png', nullable: true),
        new OA\Property(property: 'badge_image_alt_text', type: 'string', example: 'Platinum Badge', nullable: true),
        new OA\Property(property: 'order', type: 'integer', example: 1),
        new OA\Property(property: 'should_display_on_expo_hall_page', type: 'boolean', example: true),
        new OA\Property(property: 'should_display_on_lobby_page', type: 'boolean', example: true),
        new OA\Property(property: 'summit', type: 'Summit'),
        new OA\Property(property: 'type', type: 'SponsorshipType'),
    ]
)]
class SummitSponsorshipTypeSchema {}

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
