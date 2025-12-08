<?php

namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;


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
