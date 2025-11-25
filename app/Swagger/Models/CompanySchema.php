<?php

namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;


#[OA\Schema(
    schema: 'Company',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'created', type: 'integer', example: 1),
        new OA\Property(property: 'last_edited', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string'),
        new OA\Property(property: 'url', type: 'string'),
        new OA\Property(property: 'url_segment', type: 'string'),
        new OA\Property(property: 'city', type: 'string'),
        new OA\Property(property: 'state', type: 'string'),
        new OA\Property(property: 'country', type: 'string'),
        new OA\Property(property: 'description', type: 'string'),
        new OA\Property(property: 'industry', type: 'string'),
        new OA\Property(property: 'contributions', type: 'string'),
        new OA\Property(property: 'member_level', type: 'string'),
        new OA\Property(property: 'overview', type: 'string'),
        new OA\Property(property: 'products', type: 'string'),
        new OA\Property(property: 'commitment', type: 'string'),
        new OA\Property(property: 'commitment_author', type: 'string'),
        new OA\Property(property: 'logo', type: 'string'),
        new OA\Property(property: 'big_logo', type: 'string'),
        new OA\Property(property: 'color', type: 'string'),
        new OA\Property(property: 'display_on_site', type: 'boolean'),
        new OA\Property(property: 'featured', type: 'boolean'),
        new OA\Property(property: 'contact_email', type: 'string'),
        new OA\Property(property: 'admin_email', type: 'string'),
        new OA\Property(property: 'sponsorships', type: 'array', items: new OA\Items(oneOf: [new OA\Schema(type: 'integer'), new OA\Schema(ref: '#/components/schemas/SummitSponsorship'),]), description: "SummitSponsorship, IDs when used as relationship, object when included in expand"),
        new OA\Property(property: 'project_sponsorships', type: 'array', items: new OA\Items(oneOf: [new OA\Schema(type: 'integer'), new OA\Schema(ref: '#/components/schemas/ProjectSponsorshipType'),]), description: "ProjectSponsorshipType supported by the distribution, IDs when used as relationship, object when included in expand"),
    ])
]
class CompanySchema
{
}
