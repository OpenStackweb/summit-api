<?php 
namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;



#[OA\Schema(
    schema: "EventTypeAddRequest",
    type: "object",
    required: ["name", "class_name"],
    properties: [
        new OA\Property(
            property: "name",
            type: "string",
            maxLength: 255,
            description: "Event type name",
            example: "Presentation"
        ),
        new OA\Property(
            property: "class_name",
            type: "string",
            enum: ["Presentation", "Lightning Talk", "Workshop", "Keynote", "Panel"],
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
            nullable: true,
            description: "Whether event type uses sponsors",
            example: false
        ),
        new OA\Property(
            property: "are_sponsors_mandatory",
            type: "boolean",
            nullable: true,
            description: "Whether sponsors are mandatory",
            example: false
        ),
        new OA\Property(
            property: "allows_attachment",
            type: "boolean",
            nullable: true,
            description: "Whether attachments are allowed",
            example: true
        ),
        new OA\Property(
            property: "use_speakers",
            type: "boolean",
            nullable: true,
            description: "Whether event type uses speakers",
            example: true
        ),
        new OA\Property(
            property: "are_speakers_mandatory",
            type: "boolean",
            nullable: true,
            description: "Whether speakers are mandatory",
            example: true
        ),
        new OA\Property(
            property: "use_moderator",
            type: "boolean",
            nullable: true,
            description: "Whether event type uses moderators",
            example: false
        ),
        new OA\Property(
            property: "is_moderator_mandatory",
            type: "boolean",
            nullable: true,
            description: "Whether moderator is mandatory",
            example: false
        ),
        new OA\Property(
            property: "should_be_available_on_cfp",
            type: "boolean",
            nullable: true,
            description: "Whether available on Call for Proposals",
            example: true
        ),
    ]
)]
class EventTypeAddRequestSchema {}


#[OA\Schema(
    schema: "EventTypeUpdateRequest",
    type: "object",
    properties: [
        new OA\Property(
            property: "name",
            type: "string",
            maxLength: 255,
            nullable: true,
            description: "Event type name",
            example: "Presentation"
        ),
        new OA\Property(
            property: "class_name",
            type: "string",
            enum: ["Presentation", "Lightning Talk", "Workshop", "Keynote", "Panel"],
            nullable: true,
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
            nullable: true,
            description: "Whether event type uses sponsors",
            example: false
        ),
        new OA\Property(
            property: "are_sponsors_mandatory",
            type: "boolean",
            nullable: true,
            description: "Whether sponsors are mandatory",
            example: false
        ),
        new OA\Property(
            property: "allows_attachment",
            type: "boolean",
            nullable: true,
            description: "Whether attachments are allowed",
            example: true
        ),
        new OA\Property(
            property: "use_speakers",
            type: "boolean",
            nullable: true,
            description: "Whether event type uses speakers",
            example: true
        ),
        new OA\Property(
            property: "are_speakers_mandatory",
            type: "boolean",
            nullable: true,
            description: "Whether speakers are mandatory",
            example: true
        ),
        new OA\Property(
            property: "use_moderator",
            type: "boolean",
            nullable: true,
            description: "Whether event type uses moderators",
            example: false
        ),
        new OA\Property(
            property: "is_moderator_mandatory",
            type: "boolean",
            nullable: true,
            description: "Whether moderator is mandatory",
            example: false
        ),
        new OA\Property(
            property: "should_be_available_on_cfp",
            type: "boolean",
            nullable: true,
            description: "Whether available on Call for Proposals",
            example: true
        ),
    ]
)]
class EventTypeUpdateRequestSchema {}


#[OA\Schema(
    schema: "PaginatedEventTypesResponse",
    allOf: [
        new OA\Schema(ref: "#/components/schemas/PaginateDataSchemaResponse"),
        new OA\Schema(
            type: "object",
            properties: [
                new OA\Property(
                    property: "data",
                    type: "array",
                    description: "Array of event types",
                    items: new OA\Items(ref: "#/components/schemas/EventType")
                )
            ]
        )
    ]
)]
class PaginatedEventTypesResponseSchema {}

