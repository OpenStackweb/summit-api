<?php

namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;


#[OA\Schema(
    schema: 'SupportingCompany',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'created', type: 'integer', example: 1),
        new OA\Property(property: 'last_edited', type: 'integer', example: 1),
        new OA\Property(property: 'company_id', type: 'integer', example: 1),
        new OA\Property(property: 'sponsorship_type_id', type: 'integer', example: 1),
        new OA\Property(property: 'order', type: 'integer', example: 1),
        new OA\Property(property: 'company', ref: '#/components/schemas/Company', description: 'Company object, only available when expanded'),
        new OA\Property(property: 'sponsorship_type', ref: '#/components/schemas/SponsorshipType', description: 'SponsorshipType object, only available when expanded'),

    ])
]
class SupportingCompanySchema
{
}