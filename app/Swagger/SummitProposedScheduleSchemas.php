<?php

namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "SummitProposedScheduleAllowedLocation",
    properties: [
        new OA\Property(property: "id", type: "integer", example: 1),
        new OA\Property(property: "created", type: "integer", description: "Unix timestamp", example: 1640995200),
        new OA\Property(property: "last_edited", type: "integer", description: "Unix timestamp", example: 1640995200),
        new OA\Property(property: "allowed_timeframes", type: "array", items: new OA\Items(type: "integer"), description: "Array SummitProposedScheduleAllowedDay IDs or full SummitProposedScheduleAllowedDay objects when expanded", nullable: true),
        new OA\Property(property: "location_id", type: "integer", example: 10, description: "only when not expanded"),
        new OA\Property(property: "location", type: "integer", description: "ID of the SummitAbstractLocation, when not expanded, when ?expand=location, you get a SummitAbstractLocation schema object in a 'location' property"),
        new OA\Property(property: "track_id", type: "integer", example: 5, description: "only when not expanded"),
        new OA\Property(property: "track", type: "integer", description: "ID of the PresentationCategory, when not expanded, when ?expand=track, you get a PresentationCategory schema object in a 'track' property"),
    ],
)]
class SummitProposedScheduleAllowedLocation {}

#[OA\Schema(
    schema: "SummitProposedScheduleAllowedLocationRequest",
    required: ["location_id"],
    properties: [
        new OA\Property(property: "location_id", type: "integer", example: 10)
    ]
)]
class SummitProposedScheduleAllowedLocationRequest {}

#[OA\Schema(
    schema: "SummitProposedScheduleAllowedDay",
    properties: [
        new OA\Property(property: "id", type: "integer", example: 1),
        new OA\Property(property: "created", type: "integer", description: "Unix timestamp", example: 1640995200),
        new OA\Property(property: "last_edited", type: "integer", description: "Unix timestamp", example: 1640995200),
        new OA\Property(property: "allowed_location_id", type: "integer", example: 1),
        new OA\Property(property: "day", type: "integer", description: "Unix timestamp of the day", example: 1640995200),
        new OA\Property(property: "opening_hour", type: "integer", description: "Opening hour in HHMM format (0-2359)", example: 900),
        new OA\Property(property: "closing_hour", type: "integer", description: "Closing hour in HHMM format (0-2359)", example: 1700)
    ]
)]
class SummitProposedScheduleAllowedDay {}

#[OA\Schema(
    schema: "SummitProposedScheduleAllowedDayAddRequest",
    required: ["day"],
    properties: [
        new OA\Property(property: "day", type: "integer", description: "Unix timestamp of the day", example: 1640995200),
        new OA\Property(property: "opening_hour", type: "integer", description: "Opening hour in HHMM format (0-2359)", example: 900),
        new OA\Property(property: "closing_hour", type: "integer", description: "Closing hour in HHMM format (0-2359)", example: 1700)
    ]
)]
class SummitProposedScheduleAllowedDayAddRequest {}

#[OA\Schema(
    schema: "SummitProposedScheduleAllowedDayUpdateRequest",
    properties: [
        new OA\Property(property: "day", type: "integer", description: "Unix timestamp of the day", example: 1640995200),
        new OA\Property(property: "opening_hour", type: "integer", description: "Opening hour in HHMM format (0-2359)", example: 900),
        new OA\Property(property: "closing_hour", type: "integer", description: "Closing hour in HHMM format (0-2359)", example: 1700)
    ]
)]
class SummitProposedScheduleAllowedDayUpdateRequest {}
#[OA\Schema(
    schema: "SummitProposedScheduleSummitEvent",
    properties: [
        new OA\Property(property: "id", type: "integer", example: 1),
        new OA\Property(property: "created", type: "integer", description: "Unix timestamp", example: 1640995200),
        new OA\Property(property: "last_edited", type: "integer", description: "Unix timestamp", example: 1640995200),
        new OA\Property(property: "start_date", type: "integer", description: "Unix timestamp", example: 1640995200),
        new OA\Property(property: "end_date", type: "integer", description: "Unix timestamp", example: 1641081600),
        new OA\Property(property: "duration", type: "integer", description: "Duration in seconds", example: 3600),
        new OA\Property(property: "schedule_id", type: "integer", description: "ID of the SummitProposedSchedule, when not expanded, when ?expand=schedule, you get a SummitProposedSchedule schema object in a 'schedule' property"),
        new OA\Property(property: "summit_event_id", type: "integer", example: 100),
        new OA\Property(property: "summit_event", ref: "#/components/schemas/SummitEvent", description: "only present if ?expand=summit_event"),
        new OA\Property(property: "location_id", type: "integer", description: "ID of the SummitAbstractLocation, when not expanded, when ?expand=location, you get a SummitAbstractLocation schema object in a 'location' property"),
        new OA\Property(property: "created_by_id", type: "integer", example: 5, description: "not present if expanded"),
        new OA\Property(property: "created_by", ref: "#/components/schemas/Member", description: "only present if ?expand=created_by"),
        new OA\Property(property: "updated_by_id", type: "integer", example: 5, nullable: true, description: "not present if expanded"),
        new OA\Property(property: "updated_by", ref: "#/components/schemas/Member", description: "only present if ?expand=updated_by"),
    ]
)]
class SummitProposedScheduleSummitEvent {}

#[OA\Schema(
    schema: "SummitProposedSchedulePublishRequest",
    required: ["start_date", "end_date", "location_id"],
    properties: [
        new OA\Property(property: "start_date", type: "integer", description: "Unix timestamp", example: 1640995200),
        new OA\Property(property: "end_date", type: "integer", description: "Unix timestamp (must be after start_date)", example: 1641081600),
        new OA\Property(property: "duration", type: "integer", description: "Duration in seconds", example: 3600),
        new OA\Property(property: "location_id", type: "integer", description: "ID of the SummitAbstractLocation, when not expanded, when ?expand=location, you get a SummitAbstractLocation schema object in a 'location' property"),
    ]
)]
class SummitProposedSchedulePublishRequest {}

#[OA\Schema(
    schema: "SummitProposedSchedulePublishAllRequest",
    properties: [
        new OA\Property(property: "event_ids", type: "array", items: new OA\Items(type: "integer"), description: "Array of event IDs to publish")
    ]
)]
class SummitProposedSchedulePublishAllRequest {}

#[OA\Schema(
    schema: "SummitProposedScheduleLock",
    properties: [
        new OA\Property(property: "id", type: "integer", example: 1),
        new OA\Property(property: "created", type: "integer", description: "Unix timestamp", example: 1640995200),
        new OA\Property(property: "last_edited", type: "integer", description: "Unix timestamp", example: 1640995200),
        new OA\Property(property: "reason", type: "string", example: "Review in progress"),
        new OA\Property(property: "created_by_id", type: "integer", example: 5),
        new OA\Property(property: "created_by", ref: "#/components/schemas/Member", description: "only present if ?expand=created_by"),
        new OA\Property(property: "track_id", type: "integer", example: 3, description: "ID of the PresentationCategory, when not expanded, when ?expand=track, you get a PresentationCategory schema object in a 'track' property"),
    ]
)]
class SummitProposedScheduleLock {}

#[OA\Schema(
    schema: "SummitProposedScheduleLockRequest",
    properties: [
        new OA\Property(property: "message", type: "string", maxLength: 1024, example: "Sending track schedule for review")
    ]
)]
class SummitProposedScheduleLockRequest {}

#[OA\Schema(
    schema: "PaginatedSummitProposedScheduleSummitEventsResponse",
    allOf: [
        new OA\Schema(ref: "#/components/schemas/PaginateDataSchemaResponse"),
        new OA\Schema(
            type: "object",
            properties: [
                new OA\Property(
                    property: "data",
                    type: "array",
                    items: new OA\Items(ref: "#/components/schemas/SummitProposedScheduleSummitEvent")
                )
            ]
        )
    ]
)]
class PaginatedSummitProposedScheduleSummitEventsResponse {}

#[OA\Schema(
    schema: "PaginatedSummitProposedScheduleLocksResponse",
    allOf: [
        new OA\Schema(ref: "#/components/schemas/PaginateDataSchemaResponse"),
        new OA\Schema(
            type: "object",
            properties: [
                new OA\Property(
                    property: "data",
                    type: "array",
                    items: new OA\Items(ref: "#/components/schemas/SummitProposedScheduleLock")
                )
            ]
        )
    ]
)]
class PaginatedSummitProposedScheduleLocksResponse {}

#[OA\Schema(
    schema: "SummitProposedSchedulePublishAllResponse",
    properties: [
        new OA\Property(property: "id", type: "integer", example: 1),
        new OA\Property(property: "created", type: "integer", description: "Unix timestamp", example: 1640995200),
        new OA\Property(property: "last_edited", type: "integer", description: "Unix timestamp", example: 1640995200),
        new OA\Property(property: "name", type: "string", example: "Review in progress"),
        new OA\Property(property: "source", type: "string", example: "Google Calendar"),
        new OA\Property(property: "summit_id", type: "integer", example: 3),
        new OA\Property(property: "scheduled_summit_events", type: "array", items: new OA\Items(ref: "#/components/schemas/SummitProposedScheduleSummitEvent"), description: "Array of scheduled summit events, only available if it is added in expand."),
        new OA\Property(property: "locks", type: "array", items: new OA\Items(ref: "#/components/schemas/SummitProposedScheduleLock")),
        new OA\Property(property: "created_by_id", type: "integer", example: 5),
        new OA\Property(property: "created_by", ref: "#/components/schemas/Member"),
    ]
)]
class SummitProposedSchedulePublishAllResponse {}
