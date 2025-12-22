<?php

namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'PaginatedTrackQuestionTemplatesResponse',
    allOf: [
        new OA\Schema(ref: '#/components/schemas/PaginateDataSchemaResponse'),
        new OA\Schema(
            type: 'object',
            properties: [
                new OA\Property(
                    property: 'data',
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/TrackQuestionTemplate')
                )
            ]
        )
    ]
)]
class PaginatedTrackQuestionTemplatesResponse {}

#[OA\Schema(
    schema: 'TrackQuestionValueTemplate',
    type: 'object',
    required: ['id', 'created', 'last_edited', 'value', 'label', 'order', 'owner_id'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'created', type: 'integer', example: 1634567890),
        new OA\Property(property: 'last_edited', type: 'integer', example: 1634567890),
        new OA\Property(property: 'value', type: 'string', example: 'option_value'),
        new OA\Property(property: 'label', type: 'string', example: 'Option Label'),
        new OA\Property(property: 'order', type: 'integer', example: 1),
        new OA\Property(property: 'owner_id', type: 'integer', example: 1),
    ]
)]
class TrackQuestionValueTemplate {}

#[OA\Schema(
    schema: 'TrackQuestionTemplateRequest',
    type: 'object',
    required: ['name', 'label', 'class_name'],
    properties: [
        new OA\Property(property: 'name', type: 'string', maxLength: 255, example: 'my_question'),
        new OA\Property(property: 'label', type: 'string', example: 'What is your question?'),
        new OA\Property(property: 'class_name', type: 'string', example: 'TrackTextBoxQuestionTemplate'),
        new OA\Property(property: 'is_mandatory', type: 'boolean', example: true),
        new OA\Property(property: 'is_read_only', type: 'boolean', example: false),
        new OA\Property(property: 'tracks', type: 'array', items: new OA\Items(type: 'integer'), example: [1, 2, 3]),
        new OA\Property(property: 'initial_value', type: 'string', example: 'Default value'),
        new OA\Property(property: 'empty_string', type: 'string', example: '-- Select --'),
        new OA\Property(property: 'default_value_id', type: 'integer', example: 1),
        new OA\Property(property: 'is_multiselect', type: 'boolean', example: false),
        new OA\Property(property: 'is_country_selector', type: 'boolean', example: false),
        new OA\Property(property: 'content', type: 'string', example: 'Some literal content'),
    ]
)]
class TrackQuestionTemplateRequest {}

#[OA\Schema(
    schema: 'TrackQuestionValueTemplateRequest',
    type: 'object',
    required: ['value', 'label'],
    properties: [
        new OA\Property(property: 'value', type: 'string', example: 'option_value'),
        new OA\Property(property: 'label', type: 'string', example: 'Option Label'),
        new OA\Property(property: 'order', type: 'integer', example: 1),
    ]
)]
class TrackQuestionValueTemplateRequest {}

