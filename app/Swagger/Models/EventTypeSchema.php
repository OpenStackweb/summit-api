<?php 
namespace App\Swagger\schemas;

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
            description: "Event type name",
            example: "Presentation"
        ),
        new OA\Property(
            property: "class_name",
            type: "string",
            description: "Event class type",
            example: "Presentation"
        ),
        new OA\Property(
            property: "color",
            type: "string",
            nullable: true,
            description: "Display color (hex)",
            example: "#FF5733"
        ),
        new OA\Property(
            property: "black_out_times",
            type: "string",
            enum: ["UNLIMITED", "ONLY_MAIN_EVENTS", "BLACKOUT_TIMES"],
            nullable: true,
            description: "Blackout times setting",
            example: "UNLIMITED"
        ),
        new OA\Property(
            property: "use_sponsors",
            type: "boolean",
            description: "Whether event type uses sponsors",
            example: false
        ),
        new OA\Property(
            property: "are_sponsors_mandatory",
            type: "boolean",
            description: "Whether sponsors are mandatory",
            example: false
        ),
        new OA\Property(
            property: "allows_attachment",
            type: "boolean",
            description: "Whether attachments are allowed",
            example: true
        ),
        new OA\Property(
            property: "allows_level",
            type: "boolean",
            description: "Whether level is allowed",
            example: false
        ),
        new OA\Property(
            property: "allows_publishing_dates",
            type: "boolean",
            description: "Whether publishing dates are allowed",
            example: false
        ),
        new OA\Property(
            property: "allows_location_timeframe_collision",
            type: "boolean",
            description: "Whether location/timeframe collision is allowed",
            example: false
        ),
        new OA\Property(
            property: "allows_location",
            type: "boolean",
            description: "Whether location is allowed",
            example: true
        ),
        new OA\Property(
            property: "is_default",
            type: "boolean",
            description: "Whether this is the default event type",
            example: false
        ),
        new OA\Property(
            property: "summit_id",
            type: "integer",
            format: "int64",
            description: "Summit ID",
            example: 1
        ),
        new OA\Property(
            property: "show_always_on_schedule",
            type: "boolean",
            description: "Whether to show always on schedule",
            example: false
        ),
        new OA\Property(
            property: "summit_documents",
            type: "array",
            description: "Summit document IDs (use expand=summit_documents to get full objects)",
            items: new OA\Items(
                anyOf: [
                    new OA\Schema(type: "integer"),
                    new OA\Schema(ref: "#/components/schemas/SummitDocument")
                ]
            ),
            nullable: true
        ),
        new OA\Property(
            property: "allowed_ticket_types",
            type: "array",
            description: "Allowed ticket type IDs (use expand=allowed_ticket_types to get full objects)",
            items: new OA\Items(
                anyOf: [
                    new OA\Schema(type: "integer"),
                    new OA\Schema(ref: "#/components/schemas/SummitTicketType")
                ]
            ),
            nullable: true
        ),
        new OA\Property(
            property: "summit",
            description: "Summit ID or expanded object (use expand=summit to get full object)",
            anyOf: [
                new OA\Schema(type: "integer"),
                new OA\Schema(ref: "#/components/schemas/Summit")
            ],
            nullable: true
        ),
        new OA\Property(
            property: "created",
            type: "integer",
            format: "int64",
            description: "Creation timestamp (Unix)"
        ),
        new OA\Property(
            property: "last_edited",
            type: "integer",
            format: "int64",
            description: "Last edit timestamp (Unix)"
        ),
    ]
)]
class EventTypeSchema {}
