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
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'created', type: 'integer', example: 1),
        new OA\Property(property: 'last_edited', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string'),
        new OA\Property(property: 'url', type: 'string'),
        new OA\Property(property: 'url_segment', type: 'string'),
        new OA\Property(property: 'city', type: 'string'),
        new OA\Property(property: 'state', type: 'string'),
        new OA\Property(property: 'country', type: 'string'),
        new OA\Property(property: 'description', type: 'string'),
        new OA\Property(property: 'industry', type: 'string'),
        new OA\Property(property: 'contributions', type: 'string'),
        new OA\Property(property: 'member_level', type: 'string'),
        new OA\Property(property: 'overview', type: 'string'),
        new OA\Property(property: 'products', type: 'string'),
        new OA\Property(property: 'commitment', type: 'string'),
        new OA\Property(property: 'commitment_author', type: 'string'),
        new OA\Property(property: 'logo', type: 'string'),
        new OA\Property(property: 'big_logo', type: 'string'),
        new OA\Property(property: 'color', type: 'string'),
        new OA\Property(property: 'display_on_site', type: 'boolean'),
        new OA\Property(property: 'featured', type: 'boolean'),
        new OA\Property(property: 'contact_email', type: 'string'),
        new OA\Property(property: 'admin_email', type: 'string'),
        new OA\Property(property: 'sponsorships', type: 'array', items: new OA\Items(oneOf: [new OA\Schema(type: 'integer'), new OA\Schema(ref: '#/components/schemas/SummitSponsorship'),]), description: "SummitSponsorship, IDs when used as relationship, object when included in expand"),
        new OA\Property(property: 'project_sponsorships', type: 'array', items: new OA\Items(oneOf: [new OA\Schema(type: 'integer'), new OA\Schema(ref: '#/components/schemas/ProjectSponsorshipType'),]), description: "ProjectSponsorshipType supported by the distribution, IDs when used as relationship, object when included in expand"),
        new OA\Property(property: 'supported_regions', type: 'array', items: new OA\Items(oneOf: [new OA\Schema(type: 'integer'), new OA\Schema(ref: '#/components/schemas/RegionalSupport'),]), description: "RegionalSupport, only available on expand"),
        new OA\Property(property: 'is_compatible_with_storage', type: 'boolean', description: "RegionalSupport, only available on expand", ),
        new OA\Property(property: 'is_compatible_with_compute', type: 'boolean', description: "RegionalSupport, only available on expand", ),
        new OA\Property(property: 'is_compatible_with_federated_identity', type: 'boolean', description: "RegionalSupport, only available on expand", ),
        new OA\Property(property: 'is_compatible_with_platform', type: 'boolean', description: "RegionalSupport, only available on expand", ),
        new OA\Property(property: 'is_openstack_powered', type: 'boolean', description: "RegionalSupport, only available on expand", ),
        new OA\Property(property: 'is_openstack_tested', type: 'boolean', description: "RegionalSupport, only available on expand", ),
        new OA\Property(property: 'openstack_tested_info', type: 'string', description: "RegionalSupport, only available on expand", ),
        new OA\Property(property: 'capabilities', type: 'array', items: new OA\Items(ref: '#/components/schemas/OpenStackImplementationApiCoverage'), description: "OpenStackImplementationApiCoverage, only available on relations", ),
        new OA\Property(property: 'guests', type: 'array', items: new OA\Items(ref: '#/components/schemas/GuestOSType'), description: "GuestOSType, only available on relations", ),
        new OA\Property(property: 'hypervisors', type: 'array', items: new OA\Items(ref: '#/components/schemas/HyperVisorType'), description: "HyperVisorType, only available on relations", ),
    ]
)]
class MarketplaceDistributionResponseSchema
{
}

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
class PaginatedMarketplaceDistributionResponseSchema
{
}
