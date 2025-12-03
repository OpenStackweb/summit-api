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
class ConsultantsResponseSchema
{
}

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
class PaginatedConsultantsResponseSchema
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
                    items: new OA\Items(ref: '#/components/schemas/Distribution')
                )
            ]
        )
    ]
)]
class PaginatedMarketplaceDistributionResponseSchema
{
}

#[OA\Schema(
    schema: 'PaginatedPublicOrPrivateCloudsResponse',
    allOf: [
        new OA\Schema(ref: '#/components/schemas/PaginateDataSchemaResponse'),
        new OA\Schema(
            type: 'object',
            properties: [
                new OA\Property(
                    property: 'data',
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/CloudService')
                )
            ]
        )
    ]
)]
class PaginatedPublicOrPrivateCloudsResponseSchema
{
}
