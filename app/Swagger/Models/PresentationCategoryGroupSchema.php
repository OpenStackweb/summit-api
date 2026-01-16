<?php

namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;


#[OA\Schema(
    schema: 'PresentationCategoryGroup',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'created', type: 'integer', example: 1, format: "time_epoch"),
        new OA\Property(property: 'last_edited', type: 'integer', example: 1, format: "time_epoch"),
        new OA\Property(property: 'name', type: 'string'),
        new OA\Property(property: 'color', type: 'string', format: "color_hex"),
        new OA\Property(property: 'description', type: 'string'),
        new OA\Property(property: 'class_name', type: 'string'),
        new OA\Property(property: 'summit_id', type: 'integer'),
        new OA\Property(property: 'begin_attendee_voting_period_date', type: 'integer', format: "time_epoch"),
        new OA\Property(property: 'end_attendee_voting_period_date', type: 'integer', format: "time_epoch"),
        new OA\Property(property: 'max_attendee_votes', type: 'integer'),
        new OA\Property(property: 'tracks', type: 'array',
            description: 'Array of PresentationCategory IDs, use expand=tracks for full details of PresentationCategory',
            items: new OA\Items(type: 'integer'),
        ),
    ])
]
class PresentationCategoryGroupSchema
{
}
