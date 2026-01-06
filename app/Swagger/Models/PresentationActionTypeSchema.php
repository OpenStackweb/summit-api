<?php

namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'PresentationActionType',
    type: 'object',
    description: 'Represents an action type that can be performed on presentations (e.g., "Flag for Review", "Mark as Complete")',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'created', type: 'integer', description: 'Unix timestamp', example: 1640995200),
        new OA\Property(property: 'last_edited', type: 'integer', description: 'Unix timestamp', example: 1640995200),
        new OA\Property(property: 'label', type: 'string', example: 'Flag for Review'),
        new OA\Property(property: 'summit_id', type: 'integer', example: 123, description: 'Summit ID'),
        new OA\Property(property: 'order', type: 'integer', example: 1, description: 'Order within the selection plan (when queried in context)'),
    ]
)]
class PresentationActionTypeSchema {}
