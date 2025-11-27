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
                new OA\Property(property: 'data_centers', type: 'array', items: new OA\Items(ref: '#/components/schemas/DataCenterLocation'), description: 'List of DataCenterLocation objects associated with this CloudService, only if ?relations=data_centers is used in the query string'),
                new OA\Property(property: 'data_center_regions', type: 'array', items: new OA\Items(ref: '#/components/schemas/DataCenterRegion'), description: 'List of DataCenterRegion objects associated with this CloudService, only if ?relations=data_center_regions is used in the query string'),
            ]
        ),
    ],
)]
class CloudServiceSchema
{
}
