<?php

namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;


#[OA\Schema(
    schema: 'PresentationCategory',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'created', type: 'integer', example: 1, format: "time_epoch"),
        new OA\Property(property: 'last_edited', type: 'integer', example: 1, format: "time_epoch"),
        new OA\Property(property: 'name', type: 'string'),
        new OA\Property(property: 'description', type: 'string'),
        new OA\Property(property: 'code', type: 'string'),
        new OA\Property(property: 'slug', type: 'string'),
        new OA\Property(property: 'session_count', type: 'integer'),
        new OA\Property(property: 'alternate_count', type: 'integer'),
        new OA\Property(property: 'lightning_count', type: 'integer'),
        new OA\Property(property: 'lightning_alternate_count', type: 'integer'),
        new OA\Property(property: 'voting_visible', type: 'boolean'),
        new OA\Property(property: 'chair_visible', type: 'boolean'),
        new OA\Property(property: 'summit_id', type: 'integer'),
        new OA\Property(property: 'color', type: 'string'),
        new OA\Property(property: 'text_color', type: 'string'),
        new OA\Property(property: 'icon_url', type: 'string'),
        new OA\Property(property: 'order', type: 'integer'),
        new OA\Property(property: 'proposed_schedule_transition_time', type: 'integer'),
        new OA\Property(property: 'parent_id', type: 'integer'),
        new OA\Property(property: 'track_groups', type: 'array',
            items: new OA\Items(
                anyOf: [
                    new OA\Schema(type: 'integer'),
                    new OA\Schema(ref: '#/components/schemas/PresentationCategoryGroup')
                ]
            ),
            description: 'PresentationCategoryGroup full objects if expanded'
        ),
        new OA\Property(property: 'allowed_tags', type: 'array',
            items: new OA\Items(
                anyOf: [
                    new OA\Schema(type: 'integer'),
                    // new OA\Schema(ref: '#/components/schemas/Tag')
                ]
            ),
            description: 'Tag full objects if expanded'
        ),
        new OA\Property(property: 'extra_questions', type: 'array',
            items: new OA\Items(
                anyOf: [
                    new OA\Schema(type: 'integer'),
                    // new OA\Schema(ref: '#/components/schemas/TrackQuestionTemplate')
                ]
            ),
            description: 'TrackQuestionTemplate full objects if expanded'
        ),
        new OA\Property(property: 'selection_lists', type: 'array', items: new OA\Items(type: 'integer'),),
        new OA\Property(property: 'allowed_access_levels', type: 'array', items: new OA\Items(type: 'integer'), description: 'SummitAccessLevelType IDs, or full objects if expanded'),
        new OA\Property(property: 'proposed_schedule_allowed_locations', type: 'array',
            items: new OA\Items(
                anyOf: [
                    new OA\Schema(type: 'integer'),
                    // new OA\Schema(ref: '#/components/schemas/SummitProposedScheduleAllowedLocation')
                ]
            ),
            description: 'SummitProposedScheduleAllowedLocation full objects if expanded'
        ),
        new OA\Property(property: 'parent', ref: '#/components/schemas/PresentationCategory', description: 'PresentationCategory full object if expanded'),
        new OA\Property(property: 'subtracks', type: 'array',
            items: new OA\Items(
                anyOf: [
                    new OA\Schema(type: 'integer'),
                    new OA\Schema(ref: '#/components/schemas/PresentationCategory')
                ]
            ),
            description: 'PresentationCategory full object if expanded'
        ),
    ])
]
class PresentationCategorySchema
{
}
