<?php

namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;

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
class SummitSelectedPresentationListSchema
{
}
