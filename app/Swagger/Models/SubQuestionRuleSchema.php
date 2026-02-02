<?php

namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;


#[OA\Schema(
    schema: 'SubQuestionRule',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'created', type: 'integer', example: 1, format: "time_epoch"),
        new OA\Property(property: 'last_edited', type: 'integer', example: 1, format: "time_epoch"),
        new OA\Property(property: 'visibility', type: 'string'),
        new OA\Property(property: 'visibility_condition', type: 'string'),
        new OA\Property(property: 'answer_values', type: 'array', items: new OA\Items(type: 'string')),
        new OA\Property(property: 'answer_values_operator', type: 'string'),
        new OA\Property(property: 'order', type: 'integer'),
        new OA\Property(property: 'sub_question_id', type: 'integer'),
        new OA\Property(property: 'parent_question_id', type: 'integer'),
        new OA\Property(property: 'sub_question_rules', type: 'array', items: new OA\Items(
            oneOf: [
                new OA\Schema(ref: '#/components/schemas/ExtraQuestionTypeValue'),
                new OA\Schema(type: 'integer')
            ]
        ), description: 'ID of the ExtraQuestionTypeValue when included in relations, and full objects when expanded'),
        new OA\Property(property: 'parent_rules', type: 'array', items: new OA\Items(
            oneOf: [
                new OA\Schema(ref: '#/components/schemas/ExtraQuestionTypeValue'),
                new OA\Schema(type: 'integer')
            ]
        ), description: 'ID of the ExtraQuestionTypeValue when included in relations, and full objects when expanded'),
    ])
]
class SubQuestionRuleSchema
{
}
