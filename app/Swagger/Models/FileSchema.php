<?php

namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;


#[OA\Schema(
    schema: 'File',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'created', type: 'integer', example: 1),
        new OA\Property(property: 'last_edited', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string', example: 'example.png'),
        new OA\Property(property: 'title', type: 'string', example: 'Awesome Picture'),
        new OA\Property(property: 'url', type: 'string', example: 'https://example.com/files/example.png'),
    ])
]
class FileSchema
{
}
