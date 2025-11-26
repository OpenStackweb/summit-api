<?php

namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;

// Summit Metrics Schemas

#[OA\Schema(
    schema: "SummitMetric",
    type: "object",
    properties: [
        new OA\Property(property: "id", type: "integer"),
        new OA\Property(property: "created", type: "integer"),
        new OA\Property(property: "last_edited", type: "integer"),
        new OA\Property(property: "member_first_name", type: "string", nullable: true),
        new OA\Property(property: "member_last_name", type: "string", nullable: true),
        new OA\Property(property: "member_pic", type: "string", nullable: true),
        new OA\Property(property: "type", type: "string", enum: ["GENERAL", "LOBBY", "EVENT", "SPONSOR", "POSTER", "POSTERS", "ROOM"]),
        new OA\Property(property: "ip", type: "string", nullable: true),
        new OA\Property(property: "origin", type: "string", nullable: true),
        new OA\Property(property: "browser", type: "string", nullable: true),
        new OA\Property(property: "outgress_date", type: "integer", nullable: true),
        new OA\Property(property: "ingress_date", type: "integer"),
    ]
)]
class SummitMetric
{
}

#[OA\Schema(
    schema: "SummitMetricEnterRequest",
    type: "object",
    required: ["type"],
    properties: [
        new OA\Property(property: "type", type: "string", enum: ["GENERAL", "LOBBY", "EVENT", "SPONSOR", "POSTER", "POSTERS", "ROOM"]),
        new OA\Property(property: "source_id", type: "integer", description: "ID of the source (event, sponsor, room, etc.)"),
        new OA\Property(property: "location", type: "string", description: "Location information")
    ]
)]
class SummitMetricEnterRequest
{
}

#[OA\Schema(
    schema: "SummitMetricLeaveRequest",
    type: "object",
    required: ["type"],
    properties: [
        new OA\Property(property: "type", type: "string", enum: ["GENERAL", "LOBBY", "EVENT", "SPONSOR", "POSTER", "POSTERS", "ROOM"]),
        new OA\Property(property: "source_id", type: "integer", description: "ID of the source (event, sponsor, room, etc.)"),
        new OA\Property(property: "location", type: "string", description: "Location information")
    ]
)]
class SummitMetricLeaveRequest
{
}

#[OA\Schema(
    schema: "SummitMetricOnSiteEnterRequest",
    type: "object",
    required: ["attendee_id"],
    properties: [
        new OA\Property(property: "attendee_id", type: "integer"),
        new OA\Property(property: "room_id", type: "integer"),
        new OA\Property(property: "event_id", type: "integer"),
        new OA\Property(property: "ticket_number", type: "string"),
        new OA\Property(property: "required_access_levels", type: "array", items: new OA\Items(type: "integer")),
        new OA\Property(property: "check_ingress", type: "boolean")
    ]
)]
class SummitMetricOnSiteEnterRequest
{
}

#[OA\Schema(
    schema: "SummitMetricOnSiteLeaveRequest",
    type: "object",
    required: ["attendee_id"],
    properties: [
        new OA\Property(property: "attendee_id", type: "integer"),
        new OA\Property(property: "room_id", type: "integer"),
        new OA\Property(property: "event_id", type: "integer"),
        new OA\Property(property: "required_access_levels", type: "array", items: new OA\Items(type: "integer"))
    ]
)]
class SummitMetricOnSiteLeaveRequest
{
}

#[OA\Schema(
    schema: "SummitMetricCheckOnSiteEnterRequest",
    type: "object",
    required: ["attendee_id"],
    properties: [
        new OA\Property(property: "attendee_id", type: "integer"),
        new OA\Property(property: "room_id", type: "integer"),
        new OA\Property(property: "event_id", type: "integer")
    ]
)]
class SummitMetricCheckOnSiteEnterRequest
{
}

//

