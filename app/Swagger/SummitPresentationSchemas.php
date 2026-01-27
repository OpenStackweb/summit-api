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


#[OA\Schema(
    schema: 'PaginatedPresentationCategoriesResponse',
    type: 'object',
    description: 'Paginated response containing presentation categories',
    allOf: [
        new OA\Schema(ref: '#/components/schemas/PaginateDataSchemaResponse'),
        new OA\Schema(
            type: 'object',
            properties: [
                new OA\Property(
                    property: 'data',
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/PresentationCategory'),
                    description: 'Array of presentation categories'
                )
            ]
        )
    ]
)]
class PaginatedPresentationCategoriesResponseSchema
{
}


#[OA\Schema(
    schema: 'PaginatedTraksExtraQuestionsResponse',
    type: 'object',
    description: 'Paginated response containing presentation categories',
    allOf: [
        new OA\Schema(ref: '#/components/schemas/PaginateDataSchemaResponse'),
        new OA\Schema(
            type: 'object',
            properties: [
                new OA\Property(
                    property: 'data',
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/TrackQuestionTemplate'),
                    description: 'Array of track question templates'
                )
            ]
        )
    ]
)]
class PaginatedTraksExtraQuestionsResponseSchema
{
}

#[OA\Schema(
    schema: 'PaginatedPresentationCategoryAllowedTagResponse',
    type: 'object',
    description: 'Paginated response containing presentation categories',
    allOf: [
        new OA\Schema(ref: '#/components/schemas/PaginateDataSchemaResponse'),
        new OA\Schema(
            type: 'object',
            properties: [
                new OA\Property(
                    property: 'data',
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/PresentationCategoryAllowedTag'),
                    description: 'Array of presentation category allowed tags'
                )
            ]
        )
    ]
)]
class PaginatedPresentationCategoryAllowedTagResponseSchema
{
}



#[OA\Schema(
    schema: 'PaginatedPresentationCategoriesResponse',
    type: 'object',
    description: 'Paginated response containing presentation categories',
    allOf: [
        new OA\Schema(ref: '#/components/schemas/PaginateDataSchemaResponse'),
        new OA\Schema(
            type: 'object',
            properties: [
                new OA\Property(
                    property: 'data',
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/PresentationCategory'),
                    description: 'Array of presentation categories'
                )
            ]
        )
    ]
)]
class PaginatedPresentationCategoriesResponseSchema
{
}


#[OA\Schema(
    schema: 'PaginatedTraksExtraQuestionsResponse',
    type: 'object',
    description: 'Paginated response containing presentation categories',
    allOf: [
        new OA\Schema(ref: '#/components/schemas/PaginateDataSchemaResponse'),
        new OA\Schema(
            type: 'object',
            properties: [
                new OA\Property(
                    property: 'data',
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/TrackQuestionTemplate'),
                    description: 'Array of track question templates'
                )
            ]
        )
    ]
)]
class PaginatedTraksExtraQuestionsResponseSchema
{
}

#[OA\Schema(
    schema: 'PaginatedPresentationCategoryAllowedTagResponse',
    type: 'object',
    description: 'Paginated response containing presentation categories',
    allOf: [
        new OA\Schema(ref: '#/components/schemas/PaginateDataSchemaResponse'),
        new OA\Schema(
            type: 'object',
            properties: [
                new OA\Property(
                    property: 'data',
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/PresentationCategoryAllowedTag'),
                    description: 'Array of presentation category allowed tags'
                )
            ]
        )
    ]
)]
class PaginatedPresentationCategoryAllowedTagResponseSchema
{
}


#[OA\Schema(
    schema: 'PresentationActionType',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'created', type: 'integer', example: 1630500518),
        new OA\Property(property: 'last_edited', type: 'integer', example: 1630500518),
        new OA\Property(property: 'label', type: 'string', example: 'Review'),
        new OA\Property(property: 'summit_id', type: 'integer', example: 42, description: 'Summit ID, add ?expand=summit to get full summit object'),
        new OA\Property(property: 'order', type: 'integer', example: 1, description: 'Order within a selection plan. Only present when filtering by selection_plan_id', ),
    ]
)]
class PresentationActionTypeSchema
{
}

#[OA\Schema(
    schema: 'PaginatedPresentationActionTypesResponse',
    allOf: [
        new OA\Schema(ref: '#/components/schemas/PaginateDataSchemaResponse'),
        new OA\Schema(
            type: 'object',
            properties: [
                new OA\Property(
                    property: 'data',
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/PresentationActionType')
                )
            ]
        )
    ]
)]
class PaginatedPresentationActionTypesResponseSchema
{
}

#[OA\Schema(
    schema: 'PresentationActionTypeCreateRequest',
    type: 'object',
    required: ['label'],
    properties: [
        new OA\Property(property: 'label', type: 'string', example: 'Review', maxLength: 255),
        new OA\Property(property: 'selection_plan_id', type: 'integer', example: 42, description: 'If provided, the order field will be set within the context of the selection plan'),
    ]
)]
class PresentationActionTypeCreateRequestSchema
{
}

#[OA\Schema(
    schema: 'PresentationActionTypeUpdateRequest',
    type: 'object',
    properties: [
        new OA\Property(property: 'label', type: 'string', example: 'Review', maxLength: 255),
    ]
)]
class PresentationActionTypeUpdateRequestSchema
{
}


#[OA\Schema(
    schema: 'PresentationActionType',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'created', type: 'integer', example: 1630500518),
        new OA\Property(property: 'last_edited', type: 'integer', example: 1630500518),
        new OA\Property(property: 'label', type: 'string', example: 'Review'),
        new OA\Property(property: 'summit_id', type: 'integer', example: 42, description: 'Summit ID, add ?expand=summit to get full summit object'),
        new OA\Property(property: 'order', type: 'integer', example: 1, description: 'Order within a selection plan. Only present when filtering by selection_plan_id', ),
    ]
)]
class PresentationActionTypeSchema
{
}

#[OA\Schema(
    schema: 'PaginatedPresentationActionTypesResponse',
    allOf: [
        new OA\Schema(ref: '#/components/schemas/PaginateDataSchemaResponse'),
        new OA\Schema(
            type: 'object',
            properties: [
                new OA\Property(
                    property: 'data',
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/PresentationActionType')
                )
            ]
        )
    ]
)]
class PaginatedPresentationActionTypesResponseSchema
{
}

#[OA\Schema(
    schema: 'PresentationActionTypeCreateRequest',
    type: 'object',
    required: ['label'],
    properties: [
        new OA\Property(property: 'label', type: 'string', example: 'Review', maxLength: 255),
        new OA\Property(property: 'selection_plan_id', type: 'integer', example: 42, description: 'If provided, the order field will be set within the context of the selection plan'),
    ]
)]
class PresentationActionTypeCreateRequestSchema
{
}

#[OA\Schema(
    schema: 'PresentationActionTypeUpdateRequest',
    type: 'object',
    properties: [
        new OA\Property(property: 'label', type: 'string', example: 'Review', maxLength: 255),
    ]
)]
class PresentationActionTypeUpdateRequestSchema
{
}
