<?php

namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'SummitSelectionPlanExtraQuestionType',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'created', type: 'integer', description: 'Unix timestamp', example: 1640995200),
        new OA\Property(property: 'last_edited', type: 'integer', description: 'Unix timestamp', example: 1640995200),
        new OA\Property(property: 'name', type: 'string', example: 'audience_level'),
        new OA\Property(property: 'type', type: 'string', example: 'ComboBox', description: 'Question type (Text, TextArea, ComboBox, CheckBoxList, RadioButtonList, CheckBox, CountryComboBox)'),
        new OA\Property(property: 'label', type: 'string', example: 'What is your target audience level?'),
        new OA\Property(property: 'placeholder', type: 'string', nullable: true, example: 'Select an option'),
        new OA\Property(property: 'order', type: 'integer', example: 1),
        new OA\Property(property: 'mandatory', type: 'boolean', example: true),
        new OA\Property(property: 'max_selected_values', type: 'integer', example: 0, description: 'Maximum number of values that can be selected (0 = unlimited)'),
        new OA\Property(property: 'class', type: 'string', example: 'SummitSelectionPlanExtraQuestionType'),
        new OA\Property(property: 'summit_id', type: 'integer', example: 123, description: 'Summit ID'),
        new OA\Property(
            property: 'values',
            type: 'array',
            description: 'Array of ExtraQuestionTypeValue IDs, use expand=values for full details',
            items: new OA\Items(type: 'integer'),
            example: [1, 2, 3]
        ),
        new OA\Property(
            property: 'sub_question_rules',
            type: 'array',
            description: 'Array of SubQuestionRule IDs, use expand=sub_question_rules for full details',
            items: new OA\Items(type: 'integer'),
            example: []
        ),
        new OA\Property(
            property: 'parent_rules',
            type: 'array',
            description: 'Array of parent rule IDs, use expand=parent_rules for full details',
            items: new OA\Items(type: 'integer'),
            example: []
        ),
    ]
)]
class SummitSelectionPlanExtraQuestionTypeSchema {}
