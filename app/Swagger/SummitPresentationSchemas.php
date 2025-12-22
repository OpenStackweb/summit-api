<?php

namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'PresentationTrackChairRatingType',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'created', type: 'integer', description: 'Unix timestamp', example: 1640995200),
        new OA\Property(property: 'last_edited', type: 'integer', description: 'Unix timestamp', example: 1640995200),
        new OA\Property(property: 'name', type: 'string', example: 'Technical Merit'),
        new OA\Property(property: 'weight', type: 'number', format: 'float', example: 1.5),
        new OA\Property(property: 'order', type: 'integer', example: 1),
        new OA\Property(property: 'selection_plan_id', type: 'integer', example: 1),
        new OA\Property(
            property: 'score_types',
            type: 'array',
            items: new OA\Items(type: 'integer'),
            example: [1, 2, 3]
        )
    ]
)]
class PresentationTrackChairRatingTypeSchema {}

#[OA\Schema(
    schema: 'PaginatedPresentationTrackChairRatingTypesResponse',
    allOf: [
        new OA\Schema(ref: '#/components/schemas/PaginateDataSchemaResponse'),
        new OA\Schema(
            type: 'object',
            properties: [
                new OA\Property(
                    property: 'data',
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/PresentationTrackChairRatingType')
                )
            ]
        )
    ]
)]
class PaginatedPresentationTrackChairRatingTypesResponseSchema {}

#[OA\Schema(
    schema: 'PresentationTrackChairRatingTypeCreateRequest',
    type: 'object',
    required: ['name', 'weight'],
    properties: [
        new OA\Property(property: 'name', type: 'string', example: 'Technical Merit'),
        new OA\Property(property: 'weight', type: 'number', format: 'float', example: 1.5),
        new OA\Property(property: 'order', type: 'integer', example: 1, nullable: true),
        new OA\Property(
            property: 'score_types',
            type: 'array',
            items: new OA\Items(type: 'integer'),
            example: [1, 2, 3],
            nullable: true
        )
    ]
)]
class PresentationTrackChairRatingTypeCreateRequestSchema {}

#[OA\Schema(
    schema: 'PresentationTrackChairRatingTypeUpdateRequest',
    type: 'object',
    properties: [
        new OA\Property(property: 'name', type: 'string', example: 'Technical Merit', nullable: true),
        new OA\Property(property: 'weight', type: 'number', format: 'float', example: 1.5, nullable: true),
        new OA\Property(property: 'order', type: 'integer', example: 1, nullable: true),
        new OA\Property(
            property: 'score_types',
            type: 'array',
            items: new OA\Items(type: 'integer'),
            example: [1, 2, 3],
            nullable: true
        )
    ]
)]
class PresentationTrackChairRatingTypeUpdateRequestSchema {}

//


// Track Chair Score Types

#[OA\Schema(
    schema: "PresentationTrackChairScoreType",
    description: "Track chair score type",
    type: "object",
    properties: [
        new OA\Property(property: "id", type: "integer", example: 1),
        new OA\Property(property: "created", type: "integer", description: "Unix timestamp", example: 1640995200),
        new OA\Property(property: "last_edited", type: "integer", description: "Unix timestamp", example: 1640995200),
        new OA\Property(property: "score", type: "integer", example: 5),
        new OA\Property(property: "name", type: "string", example: "Excellent"),
        new OA\Property(property: "description", type: "string", example: "This presentation is excellent"),
        new OA\Property(property: "type_id", type: "integer", example: 10),
        new OA\Property(property: "type", type: "PresentationTrackChairRatingType"),
    ],
)]
class PresentationTrackChairScoreType {}

#[OA\Schema(
    schema: "PaginatedPresentationTrackChairScoreTypesResponse",
    description: "Paginated list of track chair score types",
    allOf: [
        new OA\Schema(ref: "#/components/schemas/PaginateDataSchemaResponse"),
        new OA\Schema(
            properties: [
                new OA\Property(
                    property: "data",
                    type: "array",
                    items: new OA\Items(ref: "#/components/schemas/PresentationTrackChairScoreType")
                )
            ]
        )
    ]
)]
class PaginatedPresentationTrackChairScoreTypesResponse {}

#[OA\Schema(
    schema: "PresentationTrackChairScoreTypeCreateRequest",
    description: "Request to create a track chair score type",
    required: ["name", "description"],
    type: "object",
    properties: [
        new OA\Property(property: "name", type: "string", example: "Excellent"),
        new OA\Property(property: "description", type: "string", example: "This presentation is excellent"),
    ]
)]
class PresentationTrackChairScoreTypeCreateRequest {}

#[OA\Schema(
    schema: "PresentationTrackChairScoreTypeUpdateRequest",
    description: "Request to update a track chair score type",
    type: "object",
    properties: [
        new OA\Property(property: "score", type: "integer", nullable: true, example: 5),
        new OA\Property(property: "name", type: "string", nullable: true, example: "Excellent"),
        new OA\Property(property: "description", type: "string", nullable: true, example: "This presentation is excellent"),
    ]
)]
class PresentationTrackChairScoreTypeUpdateRequest {}

// End Track Chair Score Types
