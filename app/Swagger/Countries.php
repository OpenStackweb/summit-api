<?php namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'ISOCountryElementSchema',
    type: 'object',
    properties: [
        'iso_code' => new OA\Property(property: 'iso_code', type: 'string', example: 'US'),
        'name' => new OA\Property(property: 'name', type: 'string', example: 'United States')
    ]
)]
class ISOCountryElementSchema {};

#[OA\Schema(
    schema: 'PaginatedISOCountryElementResponseSchema',
    type: 'object',
    allOf: [
        new OA\Schema(ref: '#/components/schemas/PaginateDataSchemaResponse'),
        new OA\Schema(
            type: 'object',
            properties: [
                new OA\Property(
                    property: 'data',
                    type: 'array',
                    items: new OA\Items(ref: "#/components/schemas/ISOCountryElementSchema")
                )
            ]
        )
    ]
)]
class PaginatedISOCountryElementResponseSchema {};
