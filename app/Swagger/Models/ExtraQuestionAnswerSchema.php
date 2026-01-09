<?php

namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'ExtraQuestionAnswer',
    type: 'object',
    description: 'Represents an answer to an extra question',
    properties: [
        // Base fields (from SilverStripeSerializer)
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'created', type: 'integer', description: 'Unix timestamp', example: 1640995200),
        new OA\Property(property: 'last_edited', type: 'integer', description: 'Unix timestamp', example: 1640995200),

        // ExtraQuestionAnswer fields
        new OA\Property(property: 'value', type: 'string', example: 'Yes, I agree', description: 'The answer value'),
        new OA\Property(property: 'question_id', type: 'integer', example: 5, description: 'ExtraQuestionType ID'),

        // Expandable relations
        new OA\Property(property: 'question', type: 'object', description: 'ExtraQuestionType object, expanded when using expand=question'),
    ]
)]
class ExtraQuestionAnswerSchema {}
