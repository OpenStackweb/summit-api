<?php

namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "SummitProposedScheduleAllowedLocation",
    properties: [
        new OA\Property(property: "id", type: "integer", example: 1),
        new OA\Property(property: "created", type: "integer", description: "Unix timestamp", example: 1640995200),
        new OA\Property(property: "last_edited", type: "integer", description: "Unix timestamp", example: 1640995200),
        new OA\Property(property: "allowed_timeframes", type: "array", items: new OA\Items(oneOf: [
            new OA\Schema(type: 'integer'),
            new OA\Schema(ref: '#/components/schemas/SummitProposedScheduleAllowedDay')
        ]), description: "Array of allowed timeframe IDs or objects when expand=allowed_timeframes",),
        new OA\Property(property: "location_id", type: "integer", example: 10, description: "only when not expanded"),
        new OA\Property(property: "location", ref: '#/components/schemas/SummitAbstractLocation', description: "only when expand=location"),
        new OA\Property(property: "track_id", type: "integer", example: 5, description: "PresentationCategory ID, use expand=track for full object details"),
    ],
)]
class SummitProposedScheduleAllowedLocationSchema {}

#[OA\Schema(
    schema: "SummitProposedScheduleAllowedLocationRequest",
    required: ["location_id"],
    properties: [
        new OA\Property(property: "location_id", type: "integer", example: 10)
    ]
)]
class SummitProposedScheduleAllowedLocationRequestSchema {}

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
class SummitProposedScheduleAllowedDaySchema {}

#[OA\Schema(
    schema: "SummitProposedScheduleAllowedDayAddRequest",
    required: ["day"],
    properties: [
        new OA\Property(property: "day", type: "integer", description: "Unix timestamp of the day", example: 1640995200),
        new OA\Property(property: "opening_hour", type: "integer", description: "Opening hour in HHMM format (0-2359)", example: 900),
        new OA\Property(property: "closing_hour", type: "integer", description: "Closing hour in HHMM format (0-2359)", example: 1700)
    ]
)]
class SummitProposedScheduleAllowedDayAddRequestSchema {}

#[OA\Schema(
    schema: "SummitProposedScheduleAllowedDayUpdateRequest",
    properties: [
        new OA\Property(property: "day", type: "integer", description: "Unix timestamp of the day", example: 1640995200),
        new OA\Property(property: "opening_hour", type: "integer", description: "Opening hour in HHMM format (0-2359)", example: 900),
        new OA\Property(property: "closing_hour", type: "integer", description: "Closing hour in HHMM format (0-2359)", example: 1700)
    ]
)]
class SummitProposedScheduleAllowedDayUpdateRequestSchema {}
