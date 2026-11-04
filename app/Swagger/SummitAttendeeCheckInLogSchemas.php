<?php

namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "SummitAttendeeCheckInLog",
    description: "Append-only record of a physical check-in / check-out of an attendee",
    properties: [
        new OA\Property(property: "id", type: "integer", example: 1),
        new OA\Property(property: "created", type: "integer", format: "int64", description: "Creation timestamp (epoch)", example: 1234567890),
        new OA\Property(property: "last_edited", type: "integer", format: "int64", description: "Last edit timestamp (epoch)", example: 1234567890),
        new OA\Property(property: "action", type: "string", enum: ["CHECKED_IN", "CHECKED_OUT"], example: "CHECKED_OUT"),
        new OA\Property(property: "source", type: "string", enum: ["ADMIN_UI", "BADGE_SCAN", "BADGE_PRINT"], example: "ADMIN_UI"),
        new OA\Property(property: "reason", type: "string", nullable: true, description: "Mandatory for CHECKED_OUT from ADMIN_UI", example: "Left the venue"),
        new OA\Property(property: "client_id", type: "string", nullable: true, description: "OAuth2 client id of the access token used"),
        new OA\Property(property: "ip_address", type: "string", nullable: true, example: "203.0.113.10"),
        new OA\Property(property: "user_agent", type: "string", nullable: true),
        new OA\Property(property: "attendee_id", type: "integer", example: 123),
        new OA\Property(property: "actor_id", type: "integer", nullable: true, description: "ID of the member that triggered the change", example: 456),
    ],
    type: "object"
)]
class SummitAttendeeCheckInLogSchema
{
}

#[OA\Schema(
    schema: "PaginatedSummitAttendeeCheckInLogsResponse",
    description: "Paginated response for Summit Attendee Check In Logs",
    properties: [
        new OA\Property(property: "total", type: "integer", example: 100),
        new OA\Property(property: "per_page", type: "integer", example: 15),
        new OA\Property(property: "current_page", type: "integer", example: 1),
        new OA\Property(property: "last_page", type: "integer", example: 7),
        new OA\Property(
            property: "data",
            type: "array",
            items: new OA\Items(ref: "#/components/schemas/SummitAttendeeCheckInLog")
        ),
    ],
    type: "object"
)]
class PaginatedSummitAttendeeCheckInLogsResponseSchema
{
}
