<?php

namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'AssignedSelectionPlanExtraQuestionType',
    type: 'object',
    description: 'Represents an extra question type assigned to a selection plan with order and editability settings',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'created', type: 'integer', description: 'Unix timestamp', example: 1640995200),
        new OA\Property(property: 'last_edited', type: 'integer', description: 'Unix timestamp', example: 1640995200),
        new OA\Property(property: 'order', type: 'integer', example: 1, description: 'Order of the question within the selection plan'),
        new OA\Property(property: 'is_editable', type: 'boolean', example: true, description: 'Whether the question can be edited'),
        new OA\Property(property: 'selection_plan_id', type: 'integer', example: 123, description: 'Selection Plan ID'),
        new OA\Property(property: 'name', type: 'string', example: 'audience_level'),
        new OA\Property(property: 'type', type: 'string', example: 'ComboBox'),
        new OA\Property(property: 'label', type: 'string', example: 'What is your target audience level?'),
        new OA\Property(property: 'placeholder', type: 'string', nullable: true, example: 'Select an option'),
        new OA\Property(property: 'mandatory', type: 'boolean', example: true),
        new OA\Property(property: 'max_selected_values', type: 'integer', example: 0),
        new OA\Property(property: 'class', type: 'string', example: 'SummitSelectionPlanExtraQuestionType'),
        new OA\Property(property: 'summit_id', type: 'integer', example: 123),
        new OA\Property(
            property: 'values',
            type: 'array',
            description: 'Array of ExtraQuestionTypeValue IDs',
            items: new OA\Items(type: 'integer'),
            example: [1, 2, 3]
        ),
    ]
)]
class AssignedSelectionPlanExtraQuestionTypeSchema {}
