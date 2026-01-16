<?php

namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'SummitPresentationComment',
    type: 'object',
    description: 'Represents a comment on a presentation',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'created', type: 'integer', description: 'Unix timestamp', example: 1640995200),
        new OA\Property(property: 'last_edited', type: 'integer', description: 'Unix timestamp', example: 1640995200),
        new OA\Property(property: 'body', type: 'string', example: 'This presentation needs more detail on implementation.'),
        new OA\Property(property: 'is_activity', type: 'boolean', example: false, description: 'Whether this is an activity log entry'),
        new OA\Property(property: 'is_public', type: 'boolean', example: true, description: 'Whether the comment is visible to speakers'),
        new OA\Property(property: 'creator_id', type: 'integer', example: 100, description: 'Member ID of the comment creator'),
        new OA\Property(property: 'presentation_id', type: 'integer', example: 456, description: 'Presentation ID'),
    ]
)]
class SummitPresentationCommentSchema {}
