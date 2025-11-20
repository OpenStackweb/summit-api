<?php

namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;


#[OA\Schema(
    schema: 'RegionalSupport',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'created', type: 'integer', example: 1),
        new OA\Property(property: 'last_edited', type: 'integer', example: 1),
        new OA\Property(
            property: 'supported_channel_types',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/SupportChannelType'),
            description: "List of SupportChannelType IDs, included only when 'supported_channel_types' is in relations"
        ),
        new OA\Property(property: 'region', ref: '#/components/schemas/Region', description: "Included only when 'region' is in expand")
    ])
]
class RegionalSupportSchema
{
}
