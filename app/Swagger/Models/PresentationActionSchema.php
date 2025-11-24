<?php

namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;

// Presentation Actions

#[OA\Schema(
    schema: 'PresentationAction',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'created', type: 'integer', example: 1633024800, description: 'Unix timestamp when created'),
        new OA\Property(property: 'last_edited', type: 'integer', example: 1633111200, description: 'Unix timestamp when last updated'),
        new OA\Property(property: 'is_completed', type: 'boolean', example: true, description: 'Whether the action has been completed'),
        new OA\Property(property: 'presentation_id', type: 'integer', example: 10, description: 'Presentation ID, use expand=presentation for full object details'),
        new OA\Property(property: 'type_id', type: 'integer', example: 5, description: 'SummitEventType ID, use expand=type for full object details'),
        new OA\Property(property: 'created_by_id', type: 'integer', nullable: true, example: 42, description: 'Member ID of the user who created this action, use expand=created_by for full object details'),
        new OA\Property(property: 'updated_by_id', type: 'integer', nullable: true, example: 42, description: 'Member ID of the user who last updated this action, use expand=updated_by for full object details'),
        new OA\Property(property: 'created_by', ref: '#/components/schemas/Member'),
        new OA\Property(property: 'updated_by', ref: '#/components/schemas/Member'),
    ],
)]
class PresentationActionSchema
{
}
