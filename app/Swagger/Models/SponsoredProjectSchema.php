<?php

namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;


#[OA\Schema(
    schema: 'SponsoredProject',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'created', type: 'integer', example: 1),
        new OA\Property(property: 'last_edited', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string'),
        new OA\Property(property: 'description', type: 'string'),
        new OA\Property(property: 'slug', type: 'string'),
        new OA\Property(property: 'is_active', type: 'boolean'),
        new OA\Property(property: 'should_show_on_nav_bar', type: 'boolean'),
        new OA\Property(property: 'site_url', type: 'string'),
        new OA\Property(property: 'logo_url', type: 'string'),
        new OA\Property(property: 'parent_project_id', type: 'integer', description: 'ID of the parent sponsored project, only available if not expanded'),
        new OA\Property(property: 'sponsorship_types', type: 'array', items: new OA\Items(ref: '#/components/schemas/ProjectSponsorshipType'), description: 'Array of ProjectSponsorshipType objects, only available if expanded'),
        new OA\Property(property: 'subprojects', type: 'array', items: new OA\Items(ref: '#/components/schemas/SponsoredProject'), description: 'List of SponsoredProject objects, only available if expanded'),
        new OA\Property(property: 'parent_project', ref: '#/components/schemas/SponsoredProject', description: 'SponsoredProject object, only available if expanded'),
    ])
]
class SponsoredProjectSchema
{
}