<?php namespace App\Swagger\Summit\EventTypes;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "EventType",
    type: "object",
    required: ["id", "name", "class_name"],
    properties: [
        new OA\Property(
            property: "id",
            type: "integer",
            format: "int64",
            example: 1
        ),
        new OA\Property(
            property: "name",
            type: "string",
            example: "Presentation"
        ),
        new OA\Property(
            property: "class_name",
            type: "string",
            example: "Presentation"
        ),
        new OA\Property(
            property: "color",
            type: "string",
            nullable: true,
            example: "#FF5733"
        ),
        new OA\Property(
            property: "black_out_times",
            type: "string",
            enum: ["UNLIMITED", "ONLY_MAIN_EVENTS", "BLACKOUT_TIMES"],
            example: "UNLIMITED"
        ),
        new OA\Property(
            property: "use_sponsors",
            type: "boolean",
            example: false
        ),
        new OA\Property(
            property: "are_sponsors_mandatory",
            type: "boolean",
            example: false
        ),
        new OA\Property(
            property: "allows_attachment",
            type: "boolean",
            example: true
        ),
        new OA\Property(
            property: "allows_level",
            type: "boolean",
            example: false
        ),
        new OA\Property(
            property: "allows_publishing_dates",
            type: "boolean",
            example: false
        ),
        new OA\Property(
            property: "allows_location_timeframe_collision",
            type: "boolean",
            example: false
        ),
        new OA\Property(
            property: "allows_location",
            type: "boolean",
            example: true
        ),
        new OA\Property(
            property: "is_default",
            type: "boolean",
            example: false
        ),
        new OA\Property(
            property: "summit_id",
            type: "integer",
            format: "int64",
            example: 1
        ),
        new OA\Property(
            property: "show_always_on_schedule",
            type: "boolean",
            example: false
        ),
        new OA\Property(
            property: "summit_documents",
            type: "array",
            description: "Summit document IDs or expanded objects",
            items: new OA\Items(type: ["integer", "SummitDocument"]),
            nullable: true
        ),
        new OA\Property(
            property: "allowed_ticket_types",
            type: "array",
            description: "Allowed ticket type IDs or expanded objects",
            items: new OA\Items(type: ["integer", "SummitTicketType"]),
            nullable: true
        ),
        new OA\Property(
            property: "summit",
            type: ["integer", "Summit"],
            description: "Summit ID or expanded object",
            nullable: true
        ),
        new OA\Property(
            property: "created",
            type: "integer",
            format: "int64"
        ),
        new OA\Property(
            property: "last_edited",
            type: "integer",
            format: "int64"
        ),
    ]
)]
class EventTypeSchema {}
