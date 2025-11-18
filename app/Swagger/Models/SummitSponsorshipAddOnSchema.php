<?php

namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;


#[OA\Schema(
    schema: 'SummitSponsorshipAddOn',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'created', type: 'integer', example: 1),
        new OA\Property(property: 'last_edited', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string', ),
        new OA\Property(property: 'type', type: 'string', ),
        new OA\Property(property: 'sponsorship_id', type: 'integer', description: 'Summit Sponsorship ID, only available when NOT expanded', ),
        new OA\Property(property: 'sponsorship', ref: '#/components/schemas/SummitSponsorship', description: 'Summit Sponsorship object, only available when expanded', ),
    ])
]
class SummitSponsorshipAddOnSchema
{
}
