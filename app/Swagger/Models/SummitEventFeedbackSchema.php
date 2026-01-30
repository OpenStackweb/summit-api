<?php

namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'SummitEventFeedback',
    type: 'object',
    description: 'Represents feedback/rating submitted for a summit event',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'created', type: 'integer', description: 'Unix timestamp', example: 1640995200),
        new OA\Property(property: 'last_edited', type: 'integer', description: 'Unix timestamp', example: 1640995200),
        new OA\Property(property: 'rate', type: 'integer', example: 5, description: 'Rating value'),
        new OA\Property(property: 'note', type: 'string', nullable: true, example: 'Great presentation, very informative!', description: 'Optional feedback note'),
        new OA\Property(property: 'created_date', type: 'integer', description: 'Unix timestamp when feedback was created', example: 1640995200),
        new OA\Property(property: 'event_id', type: 'integer', example: 123, description: 'SummitEvent ID'),
        new OA\Property(property: 'owner_id', type: 'integer', nullable: true, example: 42, description: 'Member ID of the feedback owner'),
        new OA\Property(property: 'owner', ref: '#/components/schemas/Member', description: 'Expanded when using expand=owner'),
    ]
)]
class SummitEventFeedbackSchema {}
