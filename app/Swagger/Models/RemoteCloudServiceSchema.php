<?php

namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'RemoteCloudService',
    type: 'object',
    allOf: [
        new OA\Schema(ref: '#/components/schemas/OpenStackImplementation'),
        new OA\Schema(
            type: 'object',
            properties: [
                new OA\Property(property: 'hardware_spec', type: 'string', example: 'High-performance servers with SSD storage'),
                new OA\Property(property: 'pricing_models', type: 'string', example: 'Monthly subscription, Pay-as-you-use'),
                new OA\Property(property: 'published_sla', type: 'string', example: '99.9% uptime guarantee'),
                new OA\Property(property: 'is_vendor_managed_upgrades', type: 'boolean', example: true),
            ]
        )
    ]
)]
class RemoteCloudServiceSchema
{
}
