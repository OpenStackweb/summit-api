<?php

namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;


#[OA\Schema(
    schema: 'SponsorshipType',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'created', type: 'integer', example: 1),
        new OA\Property(property: 'last_edited', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string', ),
        new OA\Property(property: 'label', type: 'string', ),
        new OA\Property(property: 'order', type: 'integer', ),
        new OA\Property(property: 'size', type: 'string', ),
    ])
]
class SponsorshipTypeSchema
{
}