<?php

namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;


#[OA\Schema(
    schema: 'CloudService',
    allOf: [
        new OA\Schema(ref: '#/components/schemas/OpenStackImplementation'),
        new OA\Schema(
            type: 'object',
            properties: [
                new OA\Property(property: 'data_centers', type: 'array', items: new OA\Items(ref: '#/components/schemas/DataCenterLocation')),
                new OA\Property(property: 'data_center_regions', type: 'array', items: new OA\Items(ref: '#/components/schemas/DataCenterRegion')),
            ]
        ),
    ],
)]
class CloudServiceSchema
{
}
