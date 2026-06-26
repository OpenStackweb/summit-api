<?php namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'SummitSponsorshipAddOnType',
    type: 'object',
    required: ['id', 'name'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', format: 'int64', example: 1),
        new OA\Property(property: 'created', type: 'integer', format: 'int64', description: 'Creation timestamp (Unix epoch)'),
        new OA\Property(property: 'last_edited', type: 'integer', format: 'int64', description: 'Last edit timestamp (Unix epoch)'),
        new OA\Property(property: 'name', type: 'string', example: 'Booth'),
    ]
)]
class SummitSponsorshipAddOnTypeSchema {}

#[OA\Schema(
    schema: 'PaginatedSummitSponsorshipAddOnTypeResponse',
    type: 'object',
    allOf: [
        new OA\Schema(ref: '#/components/schemas/PaginateDataSchemaResponse'),
        new OA\Schema(
            type: 'object',
            properties: [
                new OA\Property(
                    property: 'data',
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/SummitSponsorshipAddOnType')
                )
            ]
        )
    ]
)]
class PaginatedSummitSponsorshipAddOnTypeResponseSchema {}
