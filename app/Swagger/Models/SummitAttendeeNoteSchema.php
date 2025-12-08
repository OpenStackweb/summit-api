<?php

namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'SummitAttendeeNote',
    type: 'object',
    description: 'Note attached to an attendee (admin view with email)',
    required: ['id', 'created', 'last_edited', 'content'],
    properties: [
        new OA\Property(property: 'id', type: 'integer'),
        new OA\Property(property: 'created', type: 'integer', description: 'Unix timestamp'),
        new OA\Property(property: 'last_edited', type: 'integer', description: 'Unix timestamp'),
        new OA\Property(property: 'content', type: 'string', description: 'Note content'),
        new OA\Property(property: 'author_id', type: 'integer', example: 123, description: 'AdminMember ID. Replaced by AdminMember object when using ?expand=author'),
        new OA\Property(property: 'owner_id', type: 'integer', example: 456, description: 'SummitAttendee ID. Replaced by SummitAttendee object when using ?expand=owner'),
        new OA\Property(property: 'ticket_id', type: 'integer', example: 789, nullable: true, description: 'SummitAttendeeTicket ID. Replaced by SummitAttendeeTicket object when using ?expand=ticket'),
    ]
)]
class SummitAttendeeNoteSchema {}
