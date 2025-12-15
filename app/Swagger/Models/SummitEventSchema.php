<?php

namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'SummitEvent',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer'),
        new OA\Property(property: 'created', type: 'integer', example: 1630500518),
        new OA\Property(property: 'last_edited', type: 'integer', example: 1630500518),
        new OA\Property(property: 'title', type: 'string', example: 'This is a title'),
        new OA\Property(property: 'description', type: 'string', example: 'This is a Description'),
    ]
)]
class SummitEventSchema
{
}

