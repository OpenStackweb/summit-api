<?php

namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;

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
class PaginatedSummitTaxTypesResponseSchema
{
}

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
class SummitTaxTypeCreateRequestSchema
{
}

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
        new OA\Property(property: 'description', type: 'string', example: 'Special ribbon for speakers'),
        new OA\Property(property: 'template_content', type: 'string', example: '<div>{{name}}</div>'),
    ]
)]
class SummitBadgeFeatureTypeCreateRequestSchema {}

#[OA\Schema(
    schema: 'SummitBadgeFeatureTypeUpdateRequest',
    type: 'object',
    properties: [
        new OA\Property(property: 'name', type: 'string', example: 'VIP Ribbon'),
        new OA\Property(property: 'description', type: 'string', example: 'VIP attendee designation'),
        new OA\Property(property: 'template_content', type: 'string', example: '<div class="vip">{{name}}</div>'),
    ]
)]
class SummitBadgeFeatureTypeUpdateRequestSchema {}
