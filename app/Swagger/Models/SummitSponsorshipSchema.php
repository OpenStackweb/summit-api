<?php

namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;


#[OA\Schema(
    schema: 'SummitSponsorship',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'created', type: 'integer', example: 1),
        new OA\Property(property: 'last_edited', type: 'integer', example: 1),
        new OA\Property(property: 'type_id', type: 'integer', example: 1, description: 'SummitSponsorshipType id, only available when not expanded'),
        new OA\Property(property: 'add_ons', type: 'array', items: new OA\Items(
            oneOf: [
                new OA\Schema(type: 'integer'),
                new OA\Schema(ref: '#/components/schemas/SummitSponsorshipAddOn'),
            ]
        ), description: "SummitSponsorshipAddOn, IDs when in relations, object when included in expand"),
        new OA\Property(property: 'type', ref: '#/components/schemas/SummitSponsorshipType', description: 'SummitSponsorshipType object, only available when expanded'),
    ])
]
class SummitSponsorshipSchema
{
}