<?php

namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;


#[OA\Schema(
    schema: 'TrackQuestionTemplate',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'created', type: 'integer', example: 1),
        new OA\Property(property: 'last_edited', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string'),
        new OA\Property(property: 'label', type: 'string'),
        new OA\Property(property: 'is_mandatory', type: 'boolean'),
        new OA\Property(property: 'is_read_only', type: 'boolean'),
        new OA\Property(property: 'after_question', type: 'string'),
        new OA\Property(property: 'class_name', type: 'string'),
        new OA\Property(
            property: 'tracks',
            type: 'array',
            description: 'Array of PresentationCategory, IDs or objects when ?expand=tracks',
            items: new OA\Items(
                oneOf: [
                    new OA\Schema(type: 'integer', description: 'PresentationCategory ID'),
                    new OA\Schema(ref: '#/components/schemas/PresentationCategory')
                ]
            )
        ),
    ])
]
class TrackQuestionTemplateSchema
{
}
