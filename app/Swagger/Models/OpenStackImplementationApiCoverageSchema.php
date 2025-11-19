<?php

namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;


#[OA\Schema(
    schema: 'OpenStackImplementationApiCoverage',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'created', type: 'integer', example: 1),
        new OA\Property(property: 'last_edited', type: 'integer', example: 1),
        new OA\Property(property: 'api_coverage', type: 'integer', example: 1),
        new OA\Property(property: 'component', ref: '#/components/schemas/OpenStackComponent'),
        new OA\Property(property: 'release', ref: '#/components/schemas/OpenStackRelease'),
    ]
)]
class OpenStackImplementationApiCoverageSchema
{
}
