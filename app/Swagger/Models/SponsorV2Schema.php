<?php

namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'SponsorV2',
    type: 'object',
    allOf: [
        new OA\Schema(ref: '#/components/schemas/SponsorBase'),
        new OA\Schema(
            type: 'object',
            properties: [
                new OA\Property(property: 'sponsorships', type: 'array', items: new OA\Items(
                    oneOf: [
                        new OA\Schema(ref: '#/components/schemas/SummitSponsorship'),
                        new OA\Schema(type: 'integer')
                    ]
                ), description: 'SummitSponsorships Ids when included in relations and full objects when expanded'),
            ]
        )
    ]
)]
class SponsorV2Schema
{
}
