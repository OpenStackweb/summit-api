<?php

namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'SummitSubmissionInvitation',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'created', type: 'integer', description: 'Unix timestamp', example: 1640995200),
        new OA\Property(property: 'last_edited', type: 'integer', description: 'Unix timestamp', example: 1640995200),
        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'speaker@example.com'),
        new OA\Property(property: 'first_name', type: 'string', example: 'John'),
        new OA\Property(property: 'last_name', type: 'string', example: 'Doe'),
        new OA\Property(property: 'summit_id', type: 'integer', example: 1, description: 'Summit ID'),
        new OA\Property(property: 'is_sent', type: 'boolean', example: false),
        new OA\Property(property: 'sent_date', type: 'integer', description: 'Unix timestamp', example: 1640995200, nullable: true),
        new OA\Property(
            property: 'tags',
            type: 'array',
            items: new OA\Items(type: ['integer', 'string']),
            example: [1, 2, 3],
            description: 'Array of Tag IDs or names (when expanded) associated with the invitation',
        )
    ]
)]
class SummitSubmissionInvitationSchema {}
