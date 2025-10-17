<?php

namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "SummitProposedScheduleAllowedLocation",
    properties: [
        new OA\Property(property: "id", type: "integer", example: 1),
        new OA\Property(property: "created", type: "integer", description: "Unix timestamp", example: 1640995200),
        new OA\Property(property: "last_edited", type: "integer", description: "Unix timestamp", example: 1640995200),
        new OA\Property(property: "allowed_timeframes", type: "array", items: new OA\Items(type: ["integer", "SummitProposedScheduleAllowedDay"]), description: "Array of allowed timeframe IDs or objects when expanded", nullable: true)
    ],
    anyOf: [
        new OA\Property(property: "location_id", type: "integer", example: 10, description: "only when not expanded"),
        new OA\Property(property: "location", type: "SummitAbstractLocation", description: "only when expanded"),
        new OA\Property(property: "track_id", type: "integer", example: 5, description: "only when not expanded"),
        new OA\Property(property: "track", type: "PresentationCategory", description: "only when expanded"),
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
