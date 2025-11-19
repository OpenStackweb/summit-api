<?php

namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'RemoteCloudService',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'created', type: 'integer', example: 1),
        new OA\Property(property: 'last_edited', type: 'integer', example: 1),
        new OA\Property(property: 'class_name', type: 'string', example: 'RemoteCloudService'),
        new OA\Property(property: 'name', type: 'string', example: 'Managed OpenStack Cloud Service'),
        new OA\Property(property: 'overview', type: 'string', example: 'Remotely managed private OpenStack cloud'),
        new OA\Property(property: 'call_2_action_url', type: 'string', example: 'https://example.com/managed-cloud'),
        new OA\Property(property: 'slug', type: 'string', example: 'managed-openstack-service'),
        new OA\Property(property: 'company_id', type: 'integer', example: 1),
        new OA\Property(property: 'type_id', type: 'integer', example: 1, description: 'Marketplace Type ID'),

        new OA\Property(property: 'company', ref: '#/components/schemas/Company'),

        // @TODO: Remove this from the correspodent serializer, as there is no serializer for MarketPlaceType or create serializer for it
        // new OA\Property(property: 'type', ref: '#/components/schemas/MarketPlaceType'),
        new OA\Property(property: 'reviews', type: 'array', items: new OA\Items(ref: '#/components/schemas/MarketPlaceReview')),
        new OA\Property(property: 'supported_regions', type: 'array', items: new OA\Items(ref: '#/components/schemas/RegionalSupport')),

        new OA\Property(property: 'is_compatible_with_storage', type: 'boolean', example: true),
        new OA\Property(property: 'is_compatible_with_compute', type: 'boolean', example: true),
        new OA\Property(property: 'is_compatible_with_federated_identity', type: 'boolean', example: true),
        new OA\Property(property: 'is_compatible_with_platform', type: 'boolean', example: true),
        new OA\Property(property: 'is_openstack_powered', type: 'boolean', example: true),
        new OA\Property(property: 'is_openstack_tested', type: 'boolean', example: true),
        new OA\Property(property: 'openstack_tested_info', type: 'string', example: 'Tested with OpenStack Bobcat'),

        new OA\Property(property: 'capabilities', type: 'array', items: new OA\Items(ref: '#/components/schemas/OpenStackImplementationApiCoverage'), description: 'Only present if requested via relations'),
        new OA\Property(property: 'guests', type: 'array', items: new OA\Items(ref: '#/components/schemas/GuestOSType'), description: 'Only present if requested via relations'),
        new OA\Property(property: 'hypervisors', type: 'array', items: new OA\Items(ref: '#/components/schemas/HyperVisorType'), description: 'Only present if requested via relations'),

        new OA\Property(property: 'hardware_spec', type: 'string', example: 'High-performance servers with SSD storage'),
        new OA\Property(property: 'pricing_models', type: 'string', example: 'Monthly subscription, Pay-as-you-use'),
        new OA\Property(property: 'published_sla', type: 'string', example: '99.9% uptime guarantee'),
        new OA\Property(property: 'is_vendor_managed_upgrades', type: 'boolean', example: true),

    ]
)]
class RemoteCloudServiceSchema
{
}
