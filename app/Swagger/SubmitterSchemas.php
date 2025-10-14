<?php

namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Submitter',
    type: 'object',
    description: 'Submitter extends Member with presentation data',
    allOf: [
        new OA\Schema(ref: '#/components/schemas/Member'),
        new OA\Schema(
            type: 'object',
            properties: [
                new OA\Property(
                    property: 'accepted_presentations',
                    type: 'array',
                    items: new OA\Items(type: 'integer'),
                    description: 'Array of accepted presentation IDs. Use expand=accepted_presentations to get full objects'
                ),
                new OA\Property(
                    property: 'alternate_presentations',
                    type: 'array',
                    items: new OA\Items(type: 'integer'),
                    description: 'Array of alternate presentation IDs. Use expand=alternate_presentations to get full objects'
                ),
                new OA\Property(
                    property: 'rejected_presentations',
                    type: 'array',
                    items: new OA\Items(type: 'integer'),
                    description: 'Array of rejected presentation IDs. Use expand=rejected_presentations to get full objects'
                ),
            ]
        )
    ]
)]
class SubmitterSchemas {}