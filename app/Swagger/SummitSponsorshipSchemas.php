<?php
namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'PaginatedSponsorshipsResponse',
    allOf: [
        new OA\Schema(ref: '#/components/schemas/PaginateDataSchemaResponse'),
        new OA\Schema(
            type: 'object',
            properties: [
                new OA\Property(
                    property: 'data',
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/SummitSponsorship')
                )
            ]
        )
    ]
)]
class PaginatedSponsorshipsResponseSchema {}

#[OA\Schema(
    schema: 'PaginatedAddOnsResponse',
    allOf: [
        new OA\Schema(ref: '#/components/schemas/PaginateDataSchemaResponse'),
        new OA\Schema(
            type: 'object',
            properties: [
                new OA\Property(
                    property: 'data',
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/SummitSponsorshipAddOn')
                )
            ]
        )
    ]
)]
class PaginatedAddOnsResponseSchema {}

#[OA\Schema(
    schema: 'AddSponsorshipRequest',
    type: 'object',
    required: ['type_ids'],
    properties: [
        new OA\Property(
            property: 'type_ids',
            type: 'array',
            items: new OA\Items(type: 'integer', example: 1),
            example: [1, 2, 3],
            description: 'Array of sponsorship type IDs'
        ),
    ]
)]
class AddSponsorshipRequestSchema {}

#[OA\Schema(
    schema: 'AddAddOnRequest',
    oneOf: [
        new OA\Schema(
            required: ['name', 'type'],
            properties: [
                new OA\Property(property: 'name', type: 'string', example: 'B1', description: 'Add-on name'),
                new OA\Property(property: 'type', type: 'string', example: 'Booth', description: 'Add-on Type Name'),
            ]
        ),
        new OA\Schema(
            required: ['name', 'type_id'],
            properties: [
                new OA\Property(property: 'name', type: 'string', example: 'B1 ', description: 'Add-on name'),
                new OA\Property(property: 'type_id', type: 'integer', example: '123', description: 'Add-on Type Id'),
            ]
        )
    ]
)]
class AddAddOnRequestSchema {}

#[OA\Schema(
    schema: 'UpdateAddOnRequest',
    type: 'object',
    oneOf: [
        new OA\Schema(
            required: ['name', 'type'],
            properties: [
                new OA\Property(property: 'name', type: 'string', example: 'B1', description: 'Add-on name'),
                new OA\Property(property: 'type', type: 'string', example: 'Booth', description: 'Add-on Type Name'),
            ]
        ),
        new OA\Schema(
            required: ['name', 'type_id'],
            properties: [
                new OA\Property(property: 'name', type: 'string', example: 'B1 ', description: 'Add-on name'),
                new OA\Property(property: 'type_id', type: 'integer', example: '123', description: 'Add-on Type Id'),
            ]
        )
    ]
)]
class UpdateAddOnRequestSchema {}
