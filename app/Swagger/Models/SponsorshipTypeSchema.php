<?php

namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;

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
class SponsorshipTypeSchema
{
}
