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



#[OA\Schema(
    schema: 'SponsoredProjectRequest',
    type: 'object',
    properties: [
        new OA\Property(property: 'name', type: 'string', description: 'Project name'),
        new OA\Property(property: 'description', type: 'string', description: 'Project description'),
        new OA\Property(property: 'is_active', type: 'boolean', description: 'Whether the project is active'),
    ]
)]
class SponsoredProjectRequestSchema {}


#[OA\Schema(
    schema: 'ProjectSponsorshipTypeCreateRequest',
    type: 'object',
    properties: [
        new OA\Property(property: 'name', type: 'string', description: 'Sponsorship type name'),
        new OA\Property(property: 'description', type: 'string', description: 'Sponsorship type description'),
        new OA\Property(property: 'is_active', type: 'boolean', description: 'Whether the sponsorship type is active'),
        new OA\Property(property: 'order', type: 'integer', description: 'Display order'),
    ]
)]
class ProjectSponsorshipTypeCreateRequestSchema {}

#[OA\Schema(
    schema: 'ProjectSponsorshipTypeUpdateRequest',
    type: 'object',
    properties: [
        new OA\Property(property: 'name', type: 'string', description: 'Sponsorship type name'),
        new OA\Property(property: 'description', type: 'string', description: 'Sponsorship type description'),
        new OA\Property(property: 'is_active', type: 'boolean', description: 'Whether the sponsorship type is active'),
        new OA\Property(property: 'order', type: 'integer', description: 'Display order'),
    ]
)]
class ProjectSponsorshipTypeUpdateRequestSchema {}

#[OA\Schema(
    schema: 'AddSupportingCompanyRequest',
    type: 'object',
    required: ['company_id'],
    properties: [
        new OA\Property(property: 'company_id', type: 'integer', description: 'The company id'),
        new OA\Property(property: 'order', type: 'integer', description: 'Display order'),
    ]
)]
class AddSupportingCompanyRequestSchema {}

#[OA\Schema(
    schema: 'UpdateSupportingCompanyRequest',
    type: 'object',
    required: ['order'],
    properties: [
        new OA\Property(property: 'order', type: 'integer', description: 'Display order'),
    ]
)]
class UpdateSupportingCompanyRequestSchema {}

#[OA\Schema(
    schema: 'UploadSponsoredProjectLogoRequest',
    type: 'object',
    required: ['file'],
    properties: [
        new OA\Property(
            property: 'file',
            type: 'string',
            format: 'binary',
            description: 'The logo file to upload'
        ),
    ]
)]
class UploadSponsoredProjectLogoRequestSchema {}
