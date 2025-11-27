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
    schema: "SummitSelectedPresentationList",
    properties: [
        new OA\Property(property: "id", type: "integer", example: 1),
        new OA\Property(property: "created", type: "integer", description: "Unix timestamp", example: 1640995200),
        new OA\Property(property: "last_edited", type: "integer", description: "Unix timestamp", example: 1640995200),
        new OA\Property(property: "name", type: "string", example: "My Selection List"),
        new OA\Property(property: "type", type: "string", enum: ["Individual", "Group"], example: "Individual"),
        new OA\Property(property: "hash", type: "string", example: "abc123def456"),
        new OA\Property(property: "selected_presentations", type: "array", items: new OA\Items(type: "integer"), description: "Array of SummitSelectedPresentation IDs of collection \"selected\", full objects when ?expand=selected_presentations" ),
        new OA\Property(property: "interested_presentations", type: "array", items: new OA\Items(type: "integer"), description: "Array of SummitSelectedPresentation IDs of collection \"maybe\", full objects when ?expand=interested_presentations", nullable: true),
        new OA\Property(property: "category_id", type: "integer", example: 5, description: "PresentationCategory ID, full object when ?expand=category", nullable: true),
        new OA\Property(property: "owner_id", type: "integer", example: 10, nullable: true, description: "Member ID not present when ?expand=owner"),
        new OA\Property(property: "owner", ref: "#/components/schemas/Member", description: "Member full object when ?expand=owner)", nullable: true),
        new OA\Property(property: "selection_plan_id", type: "integer", example: 3, description: "SelectionPlan ID, full object when ?expand=selection_plan)", nullable: true),
    ]
)]
class SummitSelectedPresentationList {}

#[OA\Schema(
    schema: "SummitSelectedPresentationListReorderRequest",
    required: ["collection"],
    properties: [
        new OA\Property(property: "hash", type: "string", nullable: true, example: "abc123def456"),
        new OA\Property(property: "collection", type: "string", enum: ["selected", "maybe"], example: "selected"),
        new OA\Property(property: "presentations", type: "array", items: new OA\Items(type: "integer"), description: "Array of presentation IDs in the desired order", nullable: true),
    ]
)]
class SummitSelectedPresentationListReorderRequest {}


#[OA\Schema(
    schema: "SummitSelectedPresentationList",
    properties: [
        new OA\Property(property: "id", type: "integer", example: 1),
        new OA\Property(property: "created", type: "integer", description: "Unix timestamp", example: 1640995200),
        new OA\Property(property: "last_edited", type: "integer", description: "Unix timestamp", example: 1640995200),
        new OA\Property(property: "name", type: "string", example: "My Selection List"),
        new OA\Property(property: "type", type: "string", enum: ["Individual", "Group"], example: "Individual"),
        new OA\Property(property: "hash", type: "string", example: "abc123def456"),
        new OA\Property(property: "selected_presentations", type: "array", items: new OA\Items(type: "integer"), description: "Array of selected presentation IDs"),
        new OA\Property(property: "interested_presentations", type: "array", items: new OA\Items(type: "integer"), description: "Array of interested presentation IDs (only for Individual lists)", nullable: true),
    ],
    anyOf: [
        new OA\Property(property: "category_id", type: "integer", example: 5),
        new OA\Property(property: "category", ref: "#/components/schemas/PresentationCategory"),
        new OA\Property(property: "owner_id", type: "integer", example: 10),
        new OA\Property(property: "owner", ref: "#/components/schemas/Member"),
        new OA\Property(property: "selection_plan_id", type: "integer", example: 3),
        new OA\Property(property: "selection_plan", ref: "#/components/schemas/SelectionPlan"),
    ]
)]
class SummitSelectedPresentationList {}

#[OA\Schema(
    schema: "SummitSelectedPresentationListReorderRequest",
    required: ["collection"],
    properties: [
        new OA\Property(property: "hash", type: "string", nullable: true, example: "abc123def456"),
        new OA\Property(property: "collection", type: "string", enum: ["selected", "maybe"], example: "selected"),
        new OA\Property(property: "presentations", type: "array", items: new OA\Items(type: "integer"), description: "Array of presentation IDs in the desired order", nullable: true),
    ]
)]
class SummitSelectedPresentationListReorderRequest {}


// Summit Speaker Assistance Schemas

#[OA\Schema(
    schema: "PresentationSpeakerSummitAssistanceConfirmationRequest",
    type: "object",
    properties: [
        new OA\Property(property: "id", type: "integer", example: 1),
        new OA\Property(property: "created", type: "integer", description: "Unix timestamp", example: 1640995200),
        new OA\Property(property: "last_edited", type: "integer", description: "Unix timestamp", example: 1640995200),
        new OA\Property(property: "on_site_phone", type: "string", nullable: true),
        new OA\Property(property: "registered", type: "boolean"),
        new OA\Property(property: "is_confirmed", type: "boolean"),
        new OA\Property(property: "checked_in", type: "boolean"),
        new OA\Property(property: "summit_id", type: "integer"),
        new OA\Property(property: "speaker_email", type: "string"),
        new OA\Property(property: "speaker_full_name", type: "string"),
        new OA\Property(property: "speaker_id", type: "integer", description: "PresentationSpeaker Id, full object available in 'speaker' expand (speaker field)"),
        new OA\Property(property: "confirmation_date", type: "integer", nullable: true),
    ]
)]
class PresentationSpeakerSummitAssistanceConfirmationRequest
{
}

#[OA\Schema(
    schema: "PaginatedPresentationSpeakerSummitAssistanceConfirmationRequestsResponse",
    allOf: [
        new OA\Schema(ref: "#/components/schemas/PaginateDataSchemaResponse"),
        new OA\Schema(
            properties: [
                new OA\Property(
                    property: "data",
                    type: "array",
                    items: new OA\Items(ref: "#/components/schemas/PresentationSpeakerSummitAssistanceConfirmationRequest")
                )
            ]
        )
    ]
)]
class PaginatedPresentationSpeakerSummitAssistanceConfirmationRequestsResponse
{
}

#[OA\Schema(
    schema: "PresentationSpeakerSummitAssistanceConfirmationRequestCreateRequest",
    type: "object",
    required: ["speaker_id"],
    properties: [
        new OA\Property(property: "speaker_id", type: "integer"),
        new OA\Property(property: "on_site_phone", type: "string", maxLength: 50),
        new OA\Property(property: "registered", type: "boolean"),
        new OA\Property(property: "is_confirmed", type: "boolean"),
        new OA\Property(property: "checked_in", type: "boolean")
    ]
)]
class PresentationSpeakerSummitAssistanceConfirmationRequestCreateRequest
{
}

#[OA\Schema(
    schema: "PresentationSpeakerSummitAssistanceConfirmationRequestUpdateRequest",
    type: "object",
    properties: [
        new OA\Property(property: "on_site_phone", type: "string", maxLength: 50),
        new OA\Property(property: "registered", type: "boolean"),
        new OA\Property(property: "is_confirmed", type: "boolean"),
        new OA\Property(property: "checked_in", type: "boolean")
    ]
)]
class PresentationSpeakerSummitAssistanceConfirmationRequestUpdateRequest
{
}

//

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


// Summit Speaker Assistance Schemas

#[OA\Schema(
    schema: "PresentationSpeakerSummitAssistanceConfirmationRequest",
    type: "object",
    properties: [
        new OA\Property(property: "id", type: "integer", example: 1),
        new OA\Property(property: "created", type: "integer", description: "Unix timestamp", example: 1640995200),
        new OA\Property(property: "last_edited", type: "integer", description: "Unix timestamp", example: 1640995200),
        new OA\Property(property: "on_site_phone", type: "string", nullable: true),
        new OA\Property(property: "registered", type: "boolean"),
        new OA\Property(property: "is_confirmed", type: "boolean"),
        new OA\Property(property: "checked_in", type: "boolean"),
        new OA\Property(property: "summit_id", type: "integer"),
        new OA\Property(property: "speaker_email", type: "string"),
        new OA\Property(property: "speaker_full_name", type: "string"),
        new OA\Property(property: "speaker_id", type: "integer", description: "PresentationSpeaker Id, full object available in 'speaker' expand (speaker field)"),
        new OA\Property(property: "confirmation_date", type: "integer", nullable: true),
    ]
)]
class PresentationSpeakerSummitAssistanceConfirmationRequest
{
}

#[OA\Schema(
    schema: "PaginatedPresentationSpeakerSummitAssistanceConfirmationRequestsResponse",
    allOf: [
        new OA\Schema(ref: "#/components/schemas/PaginateDataSchemaResponse"),
        new OA\Schema(
            properties: [
                new OA\Property(
                    property: "data",
                    type: "array",
                    items: new OA\Items(ref: "#/components/schemas/PresentationSpeakerSummitAssistanceConfirmationRequest")
                )
            ]
        )
    ]
)]
class PaginatedPresentationSpeakerSummitAssistanceConfirmationRequestsResponse
{
}

#[OA\Schema(
    schema: "PresentationSpeakerSummitAssistanceConfirmationRequestCreateRequest",
    type: "object",
    required: ["speaker_id"],
    properties: [
        new OA\Property(property: "speaker_id", type: "integer"),
        new OA\Property(property: "on_site_phone", type: "string", maxLength: 50),
        new OA\Property(property: "registered", type: "boolean"),
        new OA\Property(property: "is_confirmed", type: "boolean"),
        new OA\Property(property: "checked_in", type: "boolean")
    ]
)]
class PresentationSpeakerSummitAssistanceConfirmationRequestCreateRequest
{
}

#[OA\Schema(
    schema: "PresentationSpeakerSummitAssistanceConfirmationRequestUpdateRequest",
    type: "object",
    properties: [
        new OA\Property(property: "on_site_phone", type: "string", maxLength: 50),
        new OA\Property(property: "registered", type: "boolean"),
        new OA\Property(property: "is_confirmed", type: "boolean"),
        new OA\Property(property: "checked_in", type: "boolean")
    ]
)]
class PresentationSpeakerSummitAssistanceConfirmationRequestUpdateRequest
{
}

//

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
