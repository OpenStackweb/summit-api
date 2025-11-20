<?php

namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;


#[OA\Schema(
    schema: 'CloudService',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'created', type: 'integer', example: 1),
        new OA\Property(property: 'last_edited', type: 'integer', example: 1),
        new OA\Property(property: 'class_name', type: 'string', example: 'PublicCloudService'),
        new OA\Property(property: 'name', type: 'string', example: 'AWS OpenStack Compatible Service'),
        new OA\Property(property: 'overview', type: 'string', example: 'Public cloud service with OpenStack compatibility'),
        new OA\Property(property: 'call_2_action_url', type: 'string', example: 'https://example.com/public-cloud'),
        new OA\Property(property: 'slug', type: 'string', example: 'aws-openstack-service'),
        new OA\Property(property: 'company_id', type: 'integer', example: 1),
        new OA\Property(property: 'type_id', type: 'integer', example: 1),
        new OA\Property(property: 'is_compatible_with_storage', type: 'boolean', example: true),
        new OA\Property(property: 'is_compatible_with_compute', type: 'boolean', example: true),
        new OA\Property(property: 'is_compatible_with_federated_identity', type: 'boolean', example: true),
        new OA\Property(property: 'is_compatible_with_platform', type: 'boolean', example: true),
        new OA\Property(property: 'is_openstack_powered', type: 'boolean', example: false),
        new OA\Property(property: 'is_openstack_tested', type: 'boolean', example: true),
        new OA\Property(property: 'openstack_tested_info', type: 'string', example: 'Compatible with OpenStack APIs'),
        new OA\Property(property: 'company', ref: '#/components/schemas/Company'),
        new OA\Property(property: 'type', ref: '#/components/schemas/SponsorshipType'),
        new OA\Property(property: 'reviews', type: 'array', items: new OA\Items(ref: '#/components/schemas/MarketPlaceReview')),
        new OA\Property(property: 'capabilities', type: 'array', items: new OA\Items(ref: '#/components/schemas/OpenStackImplementationApiCoverage')),
        new OA\Property(property: 'guests', type: 'array', items: new OA\Items(ref: '#/components/schemas/HyperVisorType')),
        new OA\Property(property: 'hypervisors', type: 'array', items: new OA\Items(ref: '#/components/schemas/GuestOSType')),
        new OA\Property(property: 'supported_regions', type: 'array', items: new OA\Items(ref: '#/components/schemas/RegionalSupport')),
    ]
)]
class CloudServiceSchema
{
}
