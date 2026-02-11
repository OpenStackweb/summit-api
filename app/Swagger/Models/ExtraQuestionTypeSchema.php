<?php

namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;


#[OA\Schema(
    schema: 'ExtraQuestionType',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'created', type: 'integer', example: 1, format: "time_epoch"),
        new OA\Property(property: 'last_edited', type: 'integer', example: 1, format: "time_epoch"),
        new OA\Property(property: 'name', type: 'string', ),
        new OA\Property(property: 'type', type: 'string', ),
        new OA\Property(property: 'label', type: 'string', ),
        new OA\Property(property: 'placeholder', type: 'string', ),
        new OA\Property(property: 'order', type: 'integer', ),
        new OA\Property(property: 'mandatory', type: 'boolean', ),
        new OA\Property(property: 'max_selected_values', type: 'integer', ),
        new OA\Property(property: 'class', type: 'string', ),
        new OA\Property(property: 'values', type: 'array', items: new OA\Items(
            oneOf: [
                new OA\Schema(ref: '#/components/schemas/ExtraQuestionTypeValue'),
                new OA\Schema(type: 'integer')
            ]
        ), description: 'ID of the ExtraQuestionTypeValue when included in relations, and full objects when expanded'),
        new OA\Property(property: 'sub_question_rules', type: 'array', items: new OA\Items(
            oneOf: [
                new OA\Schema(ref: '#/components/schemas/SubQuestionRule'),
                new OA\Schema(type: 'integer')
            ]
        ), description: 'Array of SubQuestionRule IDs when included in relations, and full objects when expanded'),
        new OA\Property(property: 'parent_rules', type: 'array', items: new OA\Items(
            oneOf: [
                new OA\Schema(ref: '#/components/schemas/SubQuestionRule'),
                new OA\Schema(type: 'integer')
            ]
        ), description: 'Array of SubQuestionRule IDs when included in relations, and full objects when expanded'),
    ])
]
class ExtraQuestionTypeSchema
{
}
