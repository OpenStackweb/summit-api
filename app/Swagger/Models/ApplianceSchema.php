<?php

namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;


#[OA\Schema(
    schema: 'Appliance',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'created', type: 'integer', example: 1),
        new OA\Property(property: 'last_edited', type: 'integer', example: 1),
        new OA\Property(property: 'class_name', type: 'string', example: 'Appliance'),
        new OA\Property(property: 'name', type: 'string', example: 'OpenStack Private Cloud Appliance'),
        new OA\Property(property: 'overview', type: 'string', example: 'Complete OpenStack solution'),
        new OA\Property(property: 'call_2_action_url', type: 'string', example: 'https://example.com/contact'),
        new OA\Property(property: 'slug', type: 'string', example: 'openstack-appliance'),
        new OA\Property(property: 'is_compatible_with_storage', type: 'boolean', example: true),
        new OA\Property(property: 'is_compatible_with_compute', type: 'boolean', example: true),
        new OA\Property(property: 'is_compatible_with_federated_identity', type: 'boolean', example: false),
        new OA\Property(property: 'is_compatible_with_platform', type: 'boolean', example: true),
        new OA\Property(property: 'is_openstack_powered', type: 'boolean', example: true),
        new OA\Property(property: 'is_openstack_tested', type: 'boolean', example: true),
        new OA\Property(property: 'openstack_tested_info', type: 'string', example: 'Tested with OpenStack Yoga'),
        new OA\Property(property: 'company_id', type: 'integer', example: 41, description: 'ID of the company that provides this appliance, visible only when is not expanded'),
        new OA\Property(property: 'company', ref: '#/components/schemas/Company', description: 'Company that provides this appliance, visible only when expanded'),
        new OA\Property(property: 'type_id', type: 'integer', example: 13, description: 'ID of the MarketPlaceType of this appliance, visible only when not expanded'),
        new OA\Property(property: 'reviews', type: 'array', items: new OA\Items(ref: '#/components/schemas/MarketPlaceReview', title: 'MarketPlaceReview'), description: 'Reviews of this appliance, visible only when expanded'),
        new OA\Property(property: 'capabilities', type: 'array', items: new OA\Items(ref: '#/components/schemas/OpenStackImplementationApiCoverage'), description: 'Capabilities of this appliance, visible only when requested as relation'),
        new OA\Property(property: 'hypervisors', type: 'array', items: new OA\Items(ref: '#/components/schemas/HyperVisorType'), description: 'Hypervisors of this appliance, visible only when requested as relation'),
        new OA\Property(property: 'guests', type: 'array', items: new OA\Items(ref: '#/components/schemas/GuestOSType'), description: 'GuestOSType of this appliance, visible only when requested as relation'),
        new OA\Property(property: 'supported_regions', type: 'array', items: new OA\Items(ref: '#/components/schemas/RegionalSupport'), description: 'Regional support of this appliance, visible only when requested as relation'),
        // @TODO: Investigate how to reference properly, as it is present in ApplianceSerializer, but does not to have a serializer asociated
        // new OA\Property(property: 'type', ref: '#/components/schemas/MarketPlaceType', description: 'MarketPlaceType of this appliance, visible only when expanded'),
    ],
)]
class ApplianceSchema
{
}