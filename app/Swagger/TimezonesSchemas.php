<?php 
namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'TimezonesResponse',
    type: 'object',
    properties: [
        new OA\Property(
            property: 'data',
            type: 'array',
            items: new OA\Items(type: 'string', example: 'America/New_York'),
            description: 'Array of all available timezone identifiers'
        ),
        new OA\Property(
            property: 'total',
            type: 'integer',
            example: 427,
            description: 'Total number of available timezones'
        ),
        new OA\Property(
            property: 'current_page',
            type: 'integer',
            example: 1,
            description: 'Current page number'
        ),
        new OA\Property(
            property: 'last_page',
            type: 'integer',
            example: 1,
            description: 'Last page number'
        ),
    ]
)]
class TimezonesResponseSchema {}