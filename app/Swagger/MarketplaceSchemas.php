<?php

namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;



#[OA\Schema(
    schema: 'RemoteCloudsResponse',
    type: 'object',
    properties: [
        'id' => new OA\Property(property: 'id', type: 'integer', example: 1),
        'class_name' => new OA\Property(property: 'class_name', type: 'string', example: 'RemoteCloudService'),
        'name' => new OA\Property(property: 'name', type: 'string', example: 'Managed OpenStack Cloud Service'),
        'overview' => new OA\Property(property: 'overview', type: 'string', example: 'Remotely managed private OpenStack cloud'),
        'call_2_action_url' => new OA\Property(property: 'call_2_action_url', type: 'string', example: 'https://example.com/managed-cloud'),
        'slug' => new OA\Property(property: 'slug', type: 'string', example: 'managed-openstack-service'),
        'company_id' => new OA\Property(property: 'company_id', type: 'integer', example: 1),
        'type_id' => new OA\Property(property: 'type_id', type: 'integer', example: 1),
        'is_compatible_with_storage' => new OA\Property(property: 'is_compatible_with_storage', type: 'boolean', example: true),
        'is_compatible_with_compute' => new OA\Property(property: 'is_compatible_with_compute', type: 'boolean', example: true),
        'is_compatible_with_federated_identity' => new OA\Property(property: 'is_compatible_with_federated_identity', type: 'boolean', example: true),
        'is_compatible_with_platform' => new OA\Property(property: 'is_compatible_with_platform', type: 'boolean', example: true),
        'is_openstack_powered' => new OA\Property(property: 'is_openstack_powered', type: 'boolean', example: true),
        'is_openstack_tested' => new OA\Property(property: 'is_openstack_tested', type: 'boolean', example: true),
        'openstack_tested_info' => new OA\Property(property: 'openstack_tested_info', type: 'string', example: 'Tested with OpenStack Bobcat'),
        'hardware_spec' => new OA\Property(property: 'hardware_spec', type: 'string', example: 'High-performance servers with SSD storage'),
        'pricing_models' => new OA\Property(property: 'pricing_models', type: 'string', example: 'Monthly subscription, Pay-as-you-use'),
        'published_sla' => new OA\Property(property: 'published_sla', type: 'string', example: '99.9% uptime guarantee'),
        'is_vendor_managed_upgrades' => new OA\Property(property: 'is_vendor_managed_upgrades', type: 'boolean', example: true)
    ]
)]
class RemoteCloudsResponseSchema {}

#[OA\Schema(
    schema: 'PaginatedRemoteCloudsResponse',
    allOf: [
        new OA\Schema(ref: '#/components/schemas/PaginateDataSchemaResponse'),
        new OA\Schema(
            type: 'object',
            properties: [
                new OA\Property(
                    property: 'data',
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/RemoteCloudsResponse')
                )
            ]
        )
    ]
)]
class PaginatedRemoteCloudsResponseSchema {}
