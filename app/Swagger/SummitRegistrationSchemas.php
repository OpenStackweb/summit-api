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
class SummitTaxTypeUpdateRequestSchema
{
}