<?php

namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Sponsor',
    type: 'object',
    allOf: [
        new OA\Schema(ref: '#/components/schemas/SponsorBase'),
        new OA\Schema(
            type: 'object',
            properties: [
                new OA\Property(property: 'sponsorship_id', type: 'integer'),
                new OA\Property(property: 'sponsorship', ref: '#/components/schemas/SummitSponsorship', description: 'SummitSponsorship when expanded'),
            ]
        )
    ]
)]
class SponsorSchema
{
}