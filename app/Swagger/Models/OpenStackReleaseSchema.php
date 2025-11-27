<?php

namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;


#[OA\Schema(
    schema: 'OpenStackRelease',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'created', type: 'integer', example: 1),
        new OA\Property(property: 'last_edited', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string'),
        new OA\Property(property: 'components', type: 'array', items: new OA\Items(
            oneOf: [
                new OA\Schema(type: 'integer'),
                new OA\Schema(ref: '#/components/schemas/OpenStackComponent'),
            ]
        ), description: "OpenStackComponent supported by the distribution, IDs when used as relationship, object when included in expand"),
    ])
]
class OpenStackReleaseSchema
{
}