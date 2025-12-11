<?php
namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'PaginatedSponsorshipsResponse',
    allOf: [
        new OA\Schema(ref: '#/components/schemas/PaginateDataSchemaResponse'),
        new OA\Schema(
            type: 'object',
            properties: [
                new OA\Property(
                    property: 'data',
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/SummitSponsorship')
                )
            ]
        )
    ]
)]
class PaginatedSponsorshipsResponseSchema {}

#[OA\Schema(
    schema: 'PaginatedAddOnsResponse',
    allOf: [
        new OA\Schema(ref: '#/components/schemas/PaginateDataSchemaResponse'),
        new OA\Schema(
            type: 'object',
            properties: [
                new OA\Property(
                    property: 'data',
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/SummitSponsorshipAddOn')
                )
            ]
        )
    ]
)]
class PaginatedAddOnsResponseSchema {}

#[OA\Schema(
    schema: 'AddSponsorshipRequest',
    type: 'object',
    required: ['type_ids'],
    properties: [
        new OA\Property(
            property: 'type_ids',
            type: 'array',
            items: new OA\Items(type: 'integer', example: 1),
            example: [1, 2, 3],
            description: 'Array of sponsorship type IDs'
        ),
    ]
)]
class AddSponsorshipRequestSchema {}

#[OA\Schema(
    schema: 'AddAddOnRequest',
    type: 'object',
    required: ['name', 'type'],
    properties: [
        new OA\Property(property: 'name', type: 'string', example: 'Premium Badge', description: 'Add-on name'),
        new OA\Property(property: 'type', type: 'string', example: 'badge', description: 'Add-on type'),
        new OA\Property(property: 'label', type: 'string', example: 'Premium', description: 'Add-on label'),
        new OA\Property(property: 'size', type: 'string', example: 'large', description: 'Add-on size'),
    ]
)]
class AddAddOnRequestSchema {}

#[OA\Schema(
    schema: 'UpdateAddOnRequest',
    type: 'object',
    properties: [
        new OA\Property(property: 'name', type: 'string', example: 'Premium Badge', description: 'Add-on name'),
        new OA\Property(property: 'type', type: 'string', example: 'badge', description: 'Add-on type'),
        new OA\Property(property: 'label', type: 'string', example: 'Premium', description: 'Add-on label'),
        new OA\Property(property: 'size', type: 'string', example: 'large', description: 'Add-on size'),
    ]
)]
class UpdateAddOnRequestSchema {}

#[OA\Schema(
    title: "Summit Sponsorship Type",
    description: "Summit Sponsorship Type Schema",
    type: "object",
    properties: [
        new OA\Property(property: "id", type: "integer", format: "int64"),
        new OA\Property(property: "widget_title", type: "string"),
        new OA\Property(property: "lobby_template", type: "string"),
        new OA\Property(property: "expo_hall_template", type: "string"),
        new OA\Property(property: "sponsor_page_template", type: "string"),
        new OA\Property(property: "event_page_template", type: "string"),
        new OA\Property(property: "sponsor_page_use_disqus_widget", type: "boolean"),
        new OA\Property(property: "sponsor_page_use_live_event_widget", type: "boolean"),
        new OA\Property(property: "sponsor_page_use_schedule_widget", type: "boolean"),
        new OA\Property(property: "sponsor_page_use_banner_widget", type: "boolean"),
        new OA\Property(property: "type_id", type: "integer", format: "int64"),
        new OA\Property(property: "badge_image", type: "string"),
        new OA\Property(property: "badge_image_alt_text", type: "string"),
        new OA\Property(property: "summit_id", type: "integer", format: "int64"),
        new OA\Property(property: "order", type: "integer", format: "int32"),
        new OA\Property(property: "should_display_on_expo_hall_page", type: "boolean"),
        new OA\Property(property: "should_display_on_lobby_page", type: "boolean"),
    ]
)]
class SummitSponsorshipTypeSchemas {}
