<?php

namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;

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
        new OA\Property(property: "confirmation_date", type: "integer", nullable: true),
    ],
    anyOf:[
        new OA\Property(property: "speaker_id", type: "integer"),
        new OA\Property(property: "speaker", type: "PresentationSpeaker"),
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

