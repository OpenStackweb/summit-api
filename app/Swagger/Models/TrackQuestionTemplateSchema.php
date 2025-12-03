<?php

namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;


#[OA\Schema(
    schema: 'TrackQuestionTemplate',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'created', type: 'integer', example: 1634567890),
        new OA\Property(property: 'last_edited', type: 'integer', example: 1634567890),
        new OA\Property(property: 'name', type: 'string', example: 'my_question'),
        new OA\Property(property: 'label', type: 'string', example: 'What is your question?'),
        new OA\Property(property: 'is_mandatory', type: 'boolean', example: true),
        new OA\Property(property: 'is_read_only', type: 'boolean', example: false),
        new OA\Property(property: 'after_question', type: 'string', example: 'previous_question'),
        new OA\Property(property: 'class_name', type: 'string', example: 'TrackTextBoxQuestionTemplate'),
        new OA\Property(
            property: 'tracks',
            type: 'array',
            items: new OA\Items(type: 'integer'),
            description: 'Array of PresentationCategory IDs, or full objects if ?expand=tracks',
        ),
    ]
)]
class TrackQuestionTemplateSchema {}
