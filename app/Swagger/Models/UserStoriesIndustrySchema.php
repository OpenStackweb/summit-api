<?php

namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;


#[OA\Schema(
    schema: 'UserStoriesIndustry',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'created', type: 'integer', example: 1),
        new OA\Property(property: 'last_edited', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string', example: 'Example Organization'),
        new OA\Property(property: 'active', type: 'boolean', example: true),

    ])
]
class UserStoriesIndustrySchema
{
}
