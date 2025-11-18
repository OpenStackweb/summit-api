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
