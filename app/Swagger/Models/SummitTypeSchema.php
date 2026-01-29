<?php

namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;


#[OA\Schema(
    schema: 'SummitType',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'created', type: 'integer', example: 1, format: 'int64', description: 'Unix timestamp'),
        new OA\Property(property: 'last_edited', type: 'integer', example: 1, format: 'int64', description: 'Unix timestamp'),
        new OA\Property(property: 'name', type: 'string'),
        new OA\Property(property: 'color', type: 'string'),
        new OA\Property(property: 'type', type: 'string'),

    ])
]
class SummitTypeSchema
{
}
