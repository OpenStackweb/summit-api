<?php

namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;


#[OA\Schema(
    schema: 'ConsultantsResponse',
    type: 'object',
    properties: [
        'id' => new OA\Property(property: 'id', type: 'integer', example: 1),
        'class_name' => new OA\Property(property: 'class_name', type: 'string', example: 'Consultant'),
        'name' => new OA\Property(property: 'name', type: 'string', example: 'OpenStack Consulting Services'),
        'overview' => new OA\Property(property: 'overview', type: 'string', example: 'Professional OpenStack consulting and support services'),
        'call_2_action_url' => new OA\Property(property: 'call_2_action_url', type: 'string', example: 'https://example.com/contact'),
        'slug' => new OA\Property(property: 'slug', type: 'string', example: 'openstack-consulting'),
        'company_id' => new OA\Property(property: 'company_id', type: 'integer', example: 1),
        'type_id' => new OA\Property(property: 'type_id', type: 'integer', example: 1)
    ]
)]
class ConsultantsResponseSchema {}

#[OA\Schema(
    schema: 'PaginatedConsultantsResponse',
    allOf: [
        new OA\Schema(ref: '#/components/schemas/PaginateDataSchemaResponse'),
        new OA\Schema(
            type: 'object',
            properties: [
                new OA\Property(
                    property: 'data',
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/ConsultantsResponse')
                )
            ]
        )
    ]
)]
class PaginatedConsultantsResponseSchema {}



#[OA\Schema(
    schema: 'MarketplaceDistributionResponseSchema',
    type: 'object',
    properties: [
        'id' => new OA\Property(property: 'id', type: 'integer', example: 1),
        'class_name' => new OA\Property(property: 'class_name', type: 'string', example: 'Distribution'),
        'name' => new OA\Property(property: 'name', type: 'string', example: 'Ubuntu OpenStack'),
        'overview' => new OA\Property(property: 'overview', type: 'string', example: 'Ubuntu-based OpenStack distribution'),
        'call_2_action_url' => new OA\Property(property: 'call_2_action_url', type: 'string', example: 'https://example.com/download'),
        'slug' => new OA\Property(property: 'slug', type: 'string', example: 'ubuntu-openstack'),
        'is_compatible_with_storage' => new OA\Property(property: 'is_compatible_with_storage', type: 'boolean', example: true),
        'is_compatible_with_compute' => new OA\Property(property: 'is_compatible_with_compute', type: 'boolean', example: true),
        'is_compatible_with_federated_identity' => new OA\Property(property: 'is_compatible_with_federated_identity', type: 'boolean', example: false),
        'is_compatible_with_platform' => new OA\Property(property: 'is_compatible_with_platform', type: 'boolean', example: true),
        'is_openstack_powered' => new OA\Property(property: 'is_openstack_powered', type: 'boolean', example: true),
        'is_openstack_tested' => new OA\Property(property: 'is_openstack_tested', type: 'boolean', example: true),
        'openstack_tested_info' => new OA\Property(property: 'openstack_tested_info', type: 'string', example: 'Tested with OpenStack Zed')
    ],
    anyOf: [
        'company_id' => new OA\Property(property: 'company_id', type: 'integer', example: 1),
        'company' => new OA\Property(property: 'company', type: 'Company'),
        'type_id' => new OA\Property(property: 'type_id', type: 'integer'),
        'type' => new OA\Property(property: 'type', type: 'MarketPlaceType'),
        'reviews' => new OA\Property(property: 'reviews', type: 'array', items: new OA\Items(type: 'MarketPlaceReview')),
    ]
)]
class MarketplaceDistributionResponseSchema {}

#[OA\Schema(
    schema: 'PaginatedMarketplaceDistributionResponseSchema',
    allOf: [
        new OA\Schema(ref: '#/components/schemas/PaginateDataSchemaResponse'),
        new OA\Schema(
            type: 'object',
            properties: [
                new OA\Property(
                    property: 'data',
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/MarketplaceDistributionResponseSchema')
                )
            ]
        )
    ]
)]
class PaginatedMarketplaceDistributionResponseSchema {}
