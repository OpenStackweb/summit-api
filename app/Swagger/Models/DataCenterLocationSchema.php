<?php

namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;


#[OA\Schema(
    schema: 'DataCenterLocation',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'created', type: 'integer', example: 1),
        new OA\Property(property: 'last_edited', type: 'integer', example: 1),
        new OA\Property(property: 'city', type: 'string', example: 'New York'),
        new OA\Property(property: 'state', type: 'string', example: 'NY'),
        new OA\Property(property: 'country', type: 'string', example: 'USA'),
        new OA\Property(property: 'lat', type: 'number', example: 40.7128),
        new OA\Property(property: 'lng', type: 'number', example: -74.0060),
        new OA\Property(property: 'region_id', type: 'integer', example: 1, description: 'DataCenterRegion ID'),
        new OA\Property(property: 'region', ref: '#/components/schemas/DataCenterRegion', description: 'DataCenterRegion object, only present if ?expand=region is used in the query string'),

    ])
]
class DataCenterLocationSchema
{
}
