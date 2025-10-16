<?php

namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;

// Presentation Actions

#[OA\Schema(
    schema: 'PresentationAction',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'is_completed', type: 'boolean', example: true, description: 'Whether the action has been completed'),
        new OA\Property(property: 'created', type: 'integer', example: 1633024800, description: 'Unix timestamp when created'),
        new OA\Property(property: 'last_edited', type: 'integer', example: 1633111200, description: 'Unix timestamp when last updated'),
    ],
    anyOf: [
        new OA\Property(property: 'presentation_id', type: 'integer', example: 10, description: 'The ID of the presentation'),
        new OA\Property(property: 'presentation', type: 'Presentation'),
        new OA\Property(property: 'type_id', type: 'integer', example: 5, description: 'ID of the action type (e.g., Review Video, Check Speakers)'),
        new OA\Property(property: 'type', type: 'PresentationActionType'),
        new OA\Property(property: 'created_by_id', type: 'integer', nullable: true, example: 42, description: 'ID of the user who created this action'),
        new OA\Property(property: 'created_by', type: 'Member'),
        new OA\Property(property: 'updated_by_id', type: 'integer', nullable: true, example: 42, description: 'ID of the user who last updated this action'),
        new OA\Property(property: 'updated_by', type: 'Member'),
    ],
)]
class PresentationActionSchema {}
