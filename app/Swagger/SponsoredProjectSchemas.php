<?php

namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'PaginatedSponsoredProjectsResponse',
    title: 'Paginated Sponsored Projects',
    description: 'Paginated list of sponsored projects',
    allOf: [
        new OA\Schema(ref: '#/components/schemas/PaginateDataSchemaResponse'),
        new OA\Schema(
            type: 'object',
            properties: [
                new OA\Property(
                    property: 'data',
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/SponsoredProject')
                ),
            ]
        )
    ]
)]
class PaginatedSponsoredProjectsResponseSchema {}

#[OA\Schema(
    schema: 'PaginatedProjectSponsorshipTypesResponse',
    title: 'Paginated Project Sponsorship Types',
    description: 'Paginated list of project sponsorship types',
    allOf: [
        new OA\Schema(ref: '#/components/schemas/PaginateDataSchemaResponse'),
        new OA\Schema(
            type: 'object',
            properties: [
                new OA\Property(
                    property: 'data',
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/ProjectSponsorshipType')
                ),
            ]
        )
    ]
)]
class PaginatedProjectSponsorshipTypesResponseSchema {}

#[OA\Schema(
    schema: 'PaginatedSupportingCompaniesResponse',
    title: 'Paginated Supporting Companies',
    description: 'Paginated list of supporting companies',
    allOf: [
        new OA\Schema(ref: '#/components/schemas/PaginateDataSchemaResponse'),
        new OA\Schema(
            type: 'object',
            properties: [
                new OA\Property(
                    property: 'data',
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/SupportingCompany')
                ),
            ]
        )
    ]
)]
class PaginatedSupportingCompaniesResponseSchema {}
