<?php

namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'SummitCategoryChange',
    type: 'object',
    description: 'Represents a request to change the category/track of a presentation',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'created', type: 'integer', description: 'Unix timestamp', example: 1640995200),
        new OA\Property(property: 'last_edited', type: 'integer', description: 'Unix timestamp', example: 1640995200),
        new OA\Property(property: 'reason', type: 'string', nullable: true, example: 'Better fit for this track'),
        new OA\Property(property: 'approval_date', type: 'integer', nullable: true, description: 'Unix timestamp when approved/rejected', example: 1641081600),
        new OA\Property(property: 'status', type: 'string', example: 'Pending', description: 'Status: Pending, Approved, Rejected'),
        new OA\Property(property: 'presentation_id', type: 'integer', example: 456, description: 'Presentation ID'),
        new OA\Property(property: 'new_category_id', type: 'integer', example: 10, description: 'New category/track ID'),
        new OA\Property(property: 'old_category_id', type: 'integer', example: 5, description: 'Original category/track ID'),
        new OA\Property(property: 'requester_id', type: 'integer', example: 100, description: 'Member ID who requested the change'),
        new OA\Property(property: 'aprover_id', type: 'integer', nullable: true, example: 200, description: 'Member ID who approved/rejected the change'),
    ]
)]
class SummitCategoryChangeSchema {}
