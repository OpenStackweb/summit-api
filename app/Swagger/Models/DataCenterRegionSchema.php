<?php

namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;


#[OA\Schema(
    schema: 'DataCenterRegion',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'created', type: 'integer', example: 1),
        new OA\Property(property: 'last_edited', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string', example: 'Data Center 1'),
        new OA\Property(property: 'endpoint', type: 'string', example: 'https://endpoint.example.com'),
    ])
]
class DataCenterRegionSchema
{
}
