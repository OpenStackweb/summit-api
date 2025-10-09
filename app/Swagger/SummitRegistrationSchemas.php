<?php

namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'SummitTaxType',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'created', type: 'integer', example: 1630500518),
        new OA\Property(property: 'last_edited', type: 'integer', example: 1630500518),
        new OA\Property(property: 'name', type: 'string', example: 'VAT'),
        new OA\Property(property: 'tax_id', type: 'string', example: 'VAT-001', nullable: true),
        new OA\Property(property: 'rate', type: 'number', format: 'float', example: 21.0),
        new OA\Property(property: 'summit_id', type: 'integer', example: 42),
        new OA\Property(
            property: 'ticket_types',
            type: 'array',
            items: new OA\Items(oneOf: [
                new OA\Schema(type: 'integer'),
                new OA\Schema(type: 'SummitTicketType')
            ]),
            example: [1, 2, 3],
            description: 'Array of ticket type IDs or its Model (only present when relations=ticket_types), expanded when expand includes ticket_types.'
        ),
    ]
)]
class SummitTaxTypeSchema {}

#[OA\Schema(
    schema: 'PaginatedSummitTaxTypesResponse',
    allOf: [
        new OA\Schema(ref: '#/components/schemas/PaginateDataSchemaResponse'),
        new OA\Schema(
            type: 'object',
            properties: [
                new OA\Property(
                    property: 'data',
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/SummitTaxType')
                )
            ]
        )
    ]
)]
class PaginatedSummitTaxTypesResponseSchema {}

#[OA\Schema(
    schema: 'SummitTaxTypeCreateRequest',
    type: 'object',
    required: ['name', 'rate'],
    properties: [
        new OA\Property(property: 'name', type: 'string', example: 'VAT'),
        new OA\Property(property: 'tax_id', type: 'string', example: 'VAT-001'),
        new OA\Property(property: 'rate', type: 'number', format: 'float', example: 21.0, description: 'Rate must be greater than 0'),
    ]
)]
class SummitTaxTypeCreateRequestSchema {}

#[OA\Schema(
    schema: 'SummitTaxTypeUpdateRequest',
    type: 'object',
    properties: [
        new OA\Property(property: 'name', type: 'string', example: 'VAT'),
        new OA\Property(property: 'tax_id', type: 'string', example: 'VAT-001'),
        new OA\Property(property: 'rate', type: 'number', format: 'float', example: 21.0, description: 'Rate must be greater than 0'),
    ]
)]
class SummitTaxTypeUpdateRequestSchema {}

// Summit Badge Feature Types

#[OA\Schema(
    schema: 'SummitBadgeFeatureType',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string', example: 'Speaker Ribbon'),
        new OA\Property(property: 'description', type: 'string', nullable: true, example: 'Special ribbon indicating speaker status'),
        new OA\Property(property: 'template_content', type: 'string', nullable: true, example: '<div class="speaker-badge">{{name}}</div>'),
        new OA\Property(property: 'summit_id', type: 'integer', example: 42),
        new OA\Property(property: 'image', type: 'string', nullable: true, example: 'https://example.com/images/speaker-ribbon.png'),
    ]
)]
class SummitBadgeFeatureTypeSchema {}

#[OA\Schema(
    schema: 'PaginatedSummitBadgeFeatureTypesResponse',
    allOf: [
        new OA\Schema(ref: '#/components/schemas/PaginateDataSchemaResponse'),
        new OA\Schema(
            type: 'object',
            properties: [
                new OA\Property(
                    property: 'data',
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/SummitBadgeFeatureType')
                )
            ]
        )
    ]
)]
class PaginatedSummitBadgeFeatureTypesResponseSchema {}

#[OA\Schema(
    schema: 'SummitBadgeFeatureTypeCreateRequest',
    type: 'object',
    required: ['name'],
    properties: [
        new OA\Property(property: 'name', type: 'string', example: 'Speaker Ribbon'),
        new OA\Property(property: 'description', type: 'string', nullable: true, example: 'Special ribbon for speakers'),
        new OA\Property(property: 'template_content', type: 'string', nullable: true, example: '<div>{{name}}</div>'),
    ]
)]
class SummitBadgeFeatureTypeCreateRequestSchema {}

#[OA\Schema(
    schema: 'SummitBadgeFeatureTypeUpdateRequest',
    type: 'object',
    properties: [
        new OA\Property(property: 'name', type: 'string', example: 'VIP Ribbon'),
        new OA\Property(property: 'description', type: 'string', nullable: true, example: 'VIP attendee designation'),
        new OA\Property(property: 'template_content', type: 'string', nullable: true, example: '<div class="vip">{{name}}</div>'),
    ]
)]
class SummitBadgeFeatureTypeUpdateRequestSchema {}
