<?php

namespace App\Swagger\schemas;

use App\Security\SummitScopes;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Response;

#[OA\Schema(
    schema: 'PaginatedDataSponsorshipType',
    description: 'Paginated response containing sponsorship types',
    allOf: [
        new OA\Schema(ref: '#/components/schemas/PaginateDataSchemaResponse'),
        new OA\Schema(
            type: 'object',
            properties: [
                new OA\Property(
                    property: 'data',
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/SponsorshipType'),
                    description: 'Array of sponsorship type objects'
                )
            ]
        )
    ]
)]
class PaginatedDataSponsorshipTypeSchemas {}

#[OA\Schema(
    schema: 'SponsorshipType',
    type: 'object',
    properties: [
        new OA\Property(
            property: 'id',
            type: 'integer',
            description: 'Sponsorship type identifier'
        ),
        new OA\Property(
            property: 'created',
            type: 'integer',
            format: 'int64',
            description: 'Creation timestamp (UNIX epoch)'
        ),
        new OA\Property(
            property: 'last_edited',
            type: 'integer',
            format: 'int64',
            description: 'Last modification timestamp (UNIX epoch)'
        ),
        new OA\Property(
            property: 'name',
            type: 'string',
            description: 'Sponsorship type name'
        ),
        new OA\Property(
            property: 'label',
            type: 'string',
            description: 'Sponsorship type display label'
        ),
        new OA\Property(
            property: 'order',
            type: 'integer',
            description: 'Display order'
        ),
        new OA\Property(
            property: 'size',
            type: 'string',
            description: 'Sponsorship size category (Small, Medium, Large, Big)'
        ),
    ]
)]
class SponsorshipTypeSchemas {}



#[OA\Schema(
    schema: 'SponsorshipTypeAddRequest',
    type: 'object',
    required: ['name', 'label', 'size'],
    description: 'Request to create a new sponsorship type',
    properties: [
        new OA\Property(property: 'name', type: 'string'),
        new OA\Property(property: 'label', type: 'string'),
        new OA\Property(property: 'size', type: 'string', enum: ['Small', 'Medium', 'Large', 'Big']),
    ]
)]
class SponsorshipTypeAddRequest {}


#[OA\Schema(
    schema: 'SponsorshipTypeUpdateRequest',
    type: 'object',
    description: 'Request to update an existing sponsorship type (all fields optional)',
    properties: [
        new OA\Property(property: 'name', type: 'string'),
        new OA\Property(property: 'label', type: 'string'),
        new OA\Property(property: 'size', type: 'string', enum: ['Small', 'Medium', 'Large', 'Big']),
        new OA\Property(property: 'order', type: 'integer', minimum: 1),
    ]
)]
class SponsorshipTypeUpdateRequest {}
