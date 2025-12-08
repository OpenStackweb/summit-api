<?php

namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;


#[OA\Schema(
    schema: 'SummitBadgeFeatureType',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'created', type: 'integer', example: 1),
        new OA\Property(property: 'last_edited', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string'),
        new OA\Property(property: 'description', type: 'string'),
        new OA\Property(property: 'template_content', type: 'string'),
        new OA\Property(property: 'summit_id', type: 'integer'),
        new OA\Property(property: 'image', type: 'string', format: 'url'),
    ])
]
class SummitBadgeFeatureTypeSchema
{
}
