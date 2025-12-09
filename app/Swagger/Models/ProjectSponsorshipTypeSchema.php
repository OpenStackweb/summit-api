<?php

namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;


#[OA\Schema(
    schema: 'ProjectSponsorshipType',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'created', type: 'integer', example: 1),
        new OA\Property(property: 'last_edited', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string', ),
        new OA\Property(property: 'description', type: 'string', ),
        new OA\Property(property: 'is_active', type: 'boolean', ),
        new OA\Property(property: 'order', type: 'integer', ),
        new OA\Property(property: 'sponsored_project_id', type: 'integer', ),
        new OA\Property(property: 'sponsored_project', type: 'array', items: new OA\Items(ref: '#/components/schemas/SponsoredProject'), description: "SponsoredProject associated with the sponsorship type, IDs when used as relationship, object when included in expand"),
        new OA\Property(
            property: 'supporting_companies',
            type: 'array',
            items: new OA\Items(
                oneOf: [
                    new OA\Schema(type: 'integer'),
                    new OA\Schema(ref: '#/components/schemas/SupportingCompany'),
                ]
            ),
            description: "SponsoredProject associated with the sponsorship type, IDs when used as relationship, object when included in expand"
        ),
    ]
)]
class ProjectSponsorshipTypeSchema
{
}