<?php

namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;


#[OA\Schema(
    schema: 'SummitBadgeType',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: "created", type: "integer", description: "Unix timestamp", example: 1640995200),
        new OA\Property(property: "last_edited", type: "integer", description: "Unix timestamp", example: 1640995200),
        new OA\Property(property: "name", type: "string", example: "Attendee"),
        new OA\Property(property: "description", type: "string", example: "Standard attendee badge"),
        new OA\Property(property: "template_content", type: "string", nullable: true, example: "Badge template content"),
        new OA\Property(property: "is_default", type: "boolean", example: false),
        new OA\Property(property: 'summit_id', type: 'integer', description: 'Summit ID, use expand=summit for full object details'),
        new OA\Property(
            property: 'access_levels',
            type: 'array',
            description: 'Array of SummitAccessLevelType IDs, use expand=access_levels for full details',
            items: new OA\Items(type: 'integer'),
            example: [1, 2, 3]
        ),
        new OA\Property(
            property: 'badge_features',
            type: 'array',
            description: 'Array of SummitBadgeFeatureType IDs, use expand=badge_features for full details',
            items: new OA\Items(type: 'integer'),
            example: [1, 2, 3]
        ),
        new OA\Property(
            property: 'allowed_view_types',
            type: 'array',
            description: 'Array of SummitBadgeViewType IDs, use expand=allowed_view_types for full details',
            items: new OA\Items(type: 'integer'),
            example: [1, 2, 3]
        ),
    ])
]
class SummitBadgeTypeSchema
{
}
