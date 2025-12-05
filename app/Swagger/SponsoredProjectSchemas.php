<?php

namespace App\Swagger\schemas;

use App\Security\SponsoredProjectScope;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'SponsoredProject',
    title: 'SponsoredProject',
    description: 'Represents a Sponsored Project',
    type: 'object',
    required: ['name'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', format: 'int64'),
        new OA\Property(property: 'name', type: 'string'),
        new OA\Property(property: 'slug', type: 'string'),
        new OA\Property(property: 'description', type: 'string'),
        new OA\Property(property: 'is_active', type: 'boolean'),
        new OA\Property(property: 'created', type: 'integer', format: 'int64'),
        new OA\Property(property: 'last_edited', type: 'integer', format: 'int64'),
    ]
)]
class SponsoredProjectSchemas {}

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
class PaginatedSponsoredProjectsResponse {}

#[OA\Schema(
    schema: 'ProjectSponsorshipType',
    title: 'Project Sponsorship Type',
    description: 'Represents a Project Sponsorship Type',
    type: 'object',
    required: ['name'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', format: 'int64'),
        new OA\Property(property: 'name', type: 'string'),
        new OA\Property(property: 'slug', type: 'string'),
        new OA\Property(property: 'description', type: 'string'),
        new OA\Property(property: 'is_active', type: 'boolean'),
        new OA\Property(property: 'order', type: 'integer'),
        new OA\Property(property: 'sponsored_project_id', type: 'integer', format: 'int64'),
        new OA\Property(property: 'created', type: 'integer', format: 'int64'),
        new OA\Property(property: 'last_edited', type: 'integer', format: 'int64'),
    ]
)]
class ProjectSponsorshipType {}

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
class PaginatedProjectSponsorshipTypesResponse {}

#[OA\Schema(
    schema: 'SupportingCompany',
    title: 'Supporting Company',
    description: 'Represents a Supporting Company for a Sponsorship Type',
    type: 'object',
    required: ['company_id'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', format: 'int64'),
        new OA\Property(property: 'company_id', type: 'integer', format: 'int64'),
        new OA\Property(property: 'sponsorship_type_id', type: 'integer', format: 'int64'),
        new OA\Property(property: 'order', type: 'integer'),
        new OA\Property(property: 'created', type: 'integer', format: 'int64'),
        new OA\Property(property: 'last_edited', type: 'integer', format: 'int64'),
    ]
)]
class SupportingCompany {}

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
class PaginatedSupportingCompaniesResponse {}

#[
    OA\SecurityScheme(
        type: 'oauth2',
        securityScheme: 'sponsored_projects_oauth2',
        flows: [
            new OA\Flow(
                authorizationUrl: L5_SWAGGER_CONST_AUTH_URL,
                tokenUrl: L5_SWAGGER_CONST_TOKEN_URL,
                flow: 'authorizationCode',
                scopes: [
                    SponsoredProjectScope::Read => 'Read Sponsored Projects',
                    SponsoredProjectScope::Write => 'Write Sponsored Projects',
                ],
            ),
        ],
    )
]
class SponsoredProjectsAuthSchema {}
