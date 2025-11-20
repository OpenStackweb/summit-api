<?php

namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;


#[OA\Schema(
    schema: 'SupportChannelType',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'created', type: 'integer', example: 1),
        new OA\Property(property: 'last_edited', type: 'integer', example: 1),
        new OA\Property(property: 'type', type: 'string', example: "example_type"),
    ])
]
class SupportChannelTypeSchema
{
}
