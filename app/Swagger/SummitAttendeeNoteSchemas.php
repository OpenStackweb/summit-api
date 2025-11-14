<?php

namespace App\Swagger\schemas;

use App\Security\SummitScopes;
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
    ],
    anyOf: [
        new OA\Property(property: 'author_id', type: 'integer', example: 123, description: 'Author member ID when not expanded'),
        new OA\Property(property: 'author', type: 'AdminMember', description: 'Full author member object (with email) when expanded (expand=author)'),
        new OA\Property(property: 'owner_id', type: 'integer', example: 456, description: 'Owner attendee ID when not expanded'),
        new OA\Property(property: 'owner', type: 'SummitAttendee', description: 'Full attendee object when expanded (expand=owner)'),
        new OA\Property(property: 'ticket_id', type: 'integer', example: 789, description: 'Ticket ID when not expanded', nullable: true),
        new OA\Property(property: 'ticket', type: 'SummitAttendeeTicket', description: 'Full ticket object when expanded (expand=ticket)', nullable: true),
    ]
)]
class SummitAttendeeNoteSchema {}

#[OA\Schema(
    schema: 'AttendeeNoteAddRequest',
    type: 'object',
    required: ['content'],
    properties: [
        new OA\Property(property: 'content', type: 'string', description: 'Note content'),
        new OA\Property(property: 'ticket_id', type: 'integer', description: 'Optional ticket ID', nullable: true),
    ]
)]
class AttendeeNoteAddRequestSchema {}

#[OA\Schema(
    schema: 'AttendeeNoteUpdateRequest',
    type: 'object',
    properties: [
        new OA\Property(property: 'content', type: 'string', description: 'Note content'),
        new OA\Property(property: 'ticket_id', type: 'integer', description: 'Optional ticket ID', nullable: true),
    ]
)]
class AttendeeNoteUpdateRequestSchema {}

#[OA\Schema(
    schema: 'PaginatedAttendeeNotesResponse',
    allOf: [
        new OA\Schema(ref: "#/components/schemas/PaginateDataSchemaResponse"),
        new OA\Schema(
            type: "object",
            properties: [
                new OA\Property(
                    property: "data",
                    type: "array",
                    items: new OA\Items(ref: "#/components/schemas/SummitAttendeeNote")
                )
            ]
        )
    ]
)]
class PaginatedAttendeeNotesResponseSchema {}
