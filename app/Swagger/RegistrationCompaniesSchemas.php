<?php

namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'PaginatedCompaniesResponse',
    type: 'object',
    description: 'Paginated response for registration companies',
    allOf: [
        new OA\Schema(ref: '#/components/schemas/PaginateDataSchemaResponse'),
        new OA\Schema(
            type: 'object',
            properties: [
                new OA\Property(
                    property: 'data',
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/Company')
                )
            ]
        )
    ]
)]
class PaginatedCompaniesResponseSchema {}

#[OA\Schema(
    schema: 'ImportRegistrationCompaniesRequest',
    type: 'object',
    description: 'Request to import registration companies from CSV file',
    required: ['file'],
    properties: [
        new OA\Property(
            property: 'file',
            type: 'string',
            format: 'binary',
            description: 'CSV file with company data'
        ),
    ]
)]
class ImportRegistrationCompaniesRequestSchema {}
