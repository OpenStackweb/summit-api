<?php

namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Company',
    type: 'object',
    description: 'Base company information (public view)',
    properties: [
        new OA\Property(property: 'id', type: 'integer'),
        new OA\Property(property: 'created', type: 'integer'),
        new OA\Property(property: 'last_edited', type: 'integer'),
        new OA\Property(property: 'name', type: 'string'),
        new OA\Property(property: 'url', type: 'string', format: 'url', nullable: true),
        new OA\Property(property: 'url_segment', type: 'string', nullable: true),
        new OA\Property(property: 'city', type: 'string', nullable: true),
        new OA\Property(property: 'state', type: 'string', nullable: true),
        new OA\Property(property: 'country', type: 'string', nullable: true),
        new OA\Property(property: 'description', type: 'string', nullable: true),
        new OA\Property(property: 'industry', type: 'string', nullable: true),
        new OA\Property(property: 'contributions', type: 'string', nullable: true),
        new OA\Property(property: 'member_level', type: 'string', nullable: true),
        new OA\Property(property: 'overview', type: 'string', nullable: true),
        new OA\Property(property: 'products', type: 'string', nullable: true),
        new OA\Property(property: 'commitment', type: 'string', nullable: true),
        new OA\Property(property: 'commitment_author', type: 'string', nullable: true),
        new OA\Property(property: 'logo', type: 'string', format: 'url', nullable: true),
        new OA\Property(property: 'big_logo', type: 'string', format: 'url', nullable: true),
        new OA\Property(property: 'color', type: 'string', description: 'Hex color code', nullable: true),
    ]
)]
class CompanySchema {}

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
