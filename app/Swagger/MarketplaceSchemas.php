<?php

namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'MarketplaceAppliancesResponse',
    type: 'object',
    properties: [
        'id' => new OA\Property(property: 'id', type: 'integer', example: 1),
        'class_name' => new OA\Property(property: 'class_name', type: 'string', example: 'Appliance'),
        'name' => new OA\Property(property: 'name', type: 'string', example: 'OpenStack Private Cloud Appliance'),
        'overview' => new OA\Property(property: 'overview', type: 'string', example: 'Complete OpenStack solution'),
        'call_2_action_url' => new OA\Property(property: 'call_2_action_url', type: 'string', example: 'https://example.com/contact'),
        'slug' => new OA\Property(property: 'slug', type: 'string', example: 'openstack-appliance'),
        'is_compatible_with_storage' => new OA\Property(property: 'is_compatible_with_storage', type: 'boolean', example: true),
        'is_compatible_with_compute' => new OA\Property(property: 'is_compatible_with_compute', type: 'boolean', example: true),
        'is_compatible_with_federated_identity' => new OA\Property(property: 'is_compatible_with_federated_identity', type: 'boolean', example: false),
        'is_compatible_with_platform' => new OA\Property(property: 'is_compatible_with_platform', type: 'boolean', example: true),
        'is_openstack_powered' => new OA\Property(property: 'is_openstack_powered', type: 'boolean', example: true),
        'is_openstack_tested' => new OA\Property(property: 'is_openstack_tested', type: 'boolean', example: true),
        'openstack_tested_info' => new OA\Property(property: 'openstack_tested_info', type: 'string', example: 'Tested with OpenStack Yoga')
    ],
    anyOf: [
        new OA\Schema(anyOf: [
            'company_id' => new OA\Property(property: 'company_id', type: 'integer', example: 41),
            'company' => new OA\Property(property: 'company', type: 'Company'),

        ]),
        new OA\Schema(anyOf: [
            'type_id' => new OA\Property(property: 'type_id', type: 'integer', example: 13),
            'type' => new OA\Property(property: 'type', type: 'MarketPlaceType'),
        ]),
        new OA\Property(property: 'reviews', type: 'array', items: new OA\Items(type: 'MarketPlaceReview', title: 'MarketPlaceReview')),
    ]
)]
class MarketplaceAppliancesResponseSchema {}

#[OA\Schema(
    schema: 'PaginatedMarketplaceAppliancesResponse',
    allOf: [
        new OA\Schema(ref: '#/components/schemas/PaginateDataSchemaResponse'),
        new OA\Schema(
            type: 'object',
            properties: [
                new OA\Property(
                    property: 'data',
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/MarketplaceAppliancesResponse')
                )
            ]
        )
    ]
)]
class PaginatedMarketplaceAppliancesResponseSchema {}
