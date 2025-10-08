<?php

namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'SpeakerActiveInvolvement',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'created', type: 'integer', format: 'int64', example: 1633024800),
        new OA\Property(property: 'last_edited', type: 'integer', format: 'int64', example: 1633024800),
        new OA\Property(property: 'involvement', type: 'string', example: 'Active Contributor'),
        new OA\Property(property: 'is_default', type: 'boolean', example: true),
    ]
)]
class SpeakerActiveInvolvementSchema {}

#[OA\Schema(
    schema: 'SpeakerActiveInvolvementsResponse',
    type: 'object',
    properties: [
        new OA\Property(property: 'total', type: 'integer', example: 5),
        new OA\Property(property: 'per_page', type: 'integer', example: 5),
        new OA\Property(property: 'current_page', type: 'integer', example: 1),
        new OA\Property(property: 'last_page', type: 'integer', example: 1),
        new OA\Property(
            property: 'data',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/SpeakerActiveInvolvement')
        ),
    ]
)]
class SpeakerActiveInvolvementsResponseSchema {}
