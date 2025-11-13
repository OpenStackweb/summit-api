<?php 

namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;


#[OA\Schema(
    schema: 'OpenStackRelease',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'created', type: 'integer', description: 'Unix timestamp', example: 1630500518),
        new OA\Property(property: 'last_edited', type: 'integer', description: 'Unix timestamp', example: 1630500518),
        new OA\Property(property: 'name', type: 'string', example: 'Zed'),
        new OA\Property(property: 'release_number', type: 'string', example: '2024.2'),
        new OA\Property(property: 'release_date', type: 'integer', description: 'Unix timestamp', example: 1729123200),
        new OA\Property(property: 'status', type: 'string', example: 'current'),
    ],
    anyOf: [
        new OA\Property(
            property: 'components',
            type: 'array',
            items: new OA\Items(type: 'integer'),
            description: 'Array of component IDs (only when not expanded)',
            example: [1, 2, 3]
        ),
        new OA\Property(
            property: 'components',
            type: 'array',
            items: new OA\Items(type: 'SoftwareComponent'),
            description: 'Array of component objects (only when expanded with expand=components)'
        ),
    ]
)]
class OpenStackReleaseSchema {}