<?php

namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;


#[OA\Schema(
    schema: 'SummitAbstractLocation',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'created', type: 'integer', example: 1),
        new OA\Property(property: 'last_edited', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string'),
        new OA\Property(property: 'short_name', type: 'string'),
        new OA\Property(property: 'description', type: 'string'),
        new OA\Property(property: 'location_type', type: 'string'),
        new OA\Property(property: 'order', type: 'integer'),
        new OA\Property(property: 'opening_hour', type: 'integer'),
        new OA\Property(property: 'closing_hour', type: 'integer'),
        new OA\Property(property: 'class_name', type: 'string'),
        new OA\Property(property: 'published_events', type: 'array', items: new OA\Items(type: 'integer'), description: 'Array of published event IDs'),
    ]
)]
class SummitAbstractLocationSchema
{
}
