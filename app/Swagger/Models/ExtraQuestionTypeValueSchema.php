<?php

namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'ExtraQuestionTypeValue',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'created', type: 'integer', example: 1, format: "time_epoch"),
        new OA\Property(property: 'last_edited', type: 'integer', example: 1, format: "time_epoch"),
        new OA\Property(property: 'label', type: 'string', ),
        new OA\Property(property: 'value', type: 'string', example: 'Option 1'),
        new OA\Property(property: 'order', type: 'integer', example: 1),
        new OA\Property(property: 'question_id', type: 'integer', ),
        new OA\Property(property: 'is_default', type: 'boolean', ),
        new OA\Property(property: 'question', ref: '#/components/schemas/ExtraQuestionType', ),
    ]
)]
class ExtraQuestionTypeValueSchema
{
}