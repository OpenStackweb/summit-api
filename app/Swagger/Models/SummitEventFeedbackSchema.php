<?php

namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'SummitEventFeedback',
    type: 'object',
    description: 'Feedback for a summit event',
    required: ['id', 'rate', 'event_id'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', description: 'Feedback ID'),
        new OA\Property(property: 'created', type: 'integer', description: 'Creation timestamp (Unix epoch)'),
        new OA\Property(property: 'last_edited', type: 'integer', description: 'Last edit timestamp (Unix epoch)'),
        new OA\Property(property: 'rate', type: 'integer', minimum: 0, maximum: 5, description: 'Rating from 0 to 5'),
        new OA\Property(property: 'note', type: 'string', maxLength: 500, nullable: true, description: 'Optional feedback note'),
        new OA\Property(property: 'created_date', type: 'integer', description: 'Creation date timestamp (Unix epoch)'),
        new OA\Property(property: 'event_id', type: 'integer', description: 'Associated event ID'),
        new OA\Property(property: 'owner_id', type: 'integer', description: 'Owner member ID. Replaced by owner object when using ?expand=owner'),
        new OA\Property(property: 'owner', ref: '#/components/schemas/Member', description: 'Owner full object available when using ?expand=owner'),
    ]
)]
class SummitEventFeedbackSchema {}
