<?php

namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Sponsor',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'company_id', type: 'integer', example: 1),
        new OA\Property(property: 'sponsorship_type_id', type: 'integer', example: 1),
        new OA\Property(property: 'order', type: 'integer', example: 1),
        new OA\Property(property: 'badge_scans_count', type: 'integer', example: 0),
        new OA\Property(property: 'links_count', type: 'integer', example: 0),
        new OA\Property(property: 'company', type: 'object', description: 'Company object'),
        new OA\Property(property: 'sponsorship', type: 'object', description: 'Sponsorship object'),
        new OA\Property(property: 'is_published', type: 'boolean', example: true),
        new OA\Property(property: 'sponsorships', type: 'array', items: new OA\Items(type: 'object'), nullable: true),
    ]
)]
class SponsorSchema {}
