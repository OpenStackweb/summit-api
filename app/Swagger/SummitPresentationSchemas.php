<?php

namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "SummitSelectedPresentationList",
    required: ["id", "created", "last_edited", "name", "type", "hash", "selected_presentations", "interested_presentations"],
    properties: [
        new OA\Property(property: "id", type: "integer", example: 1),
        new OA\Property(property: "created", type: "integer", description: "Unix timestamp", example: 1640995200),
        new OA\Property(property: "last_edited", type: "integer", description: "Unix timestamp", example: 1640995200),
        new OA\Property(property: "name", type: "string", example: "My Selection List"),
        new OA\Property(property: "type", type: "string", enum: ["Individual", "Group"], example: "Individual"),
        new OA\Property(property: "hash", type: "string", example: "abc123def456"),
        new OA\Property(property: "selected_presentations", type: "array", items: new OA\Items(type: "integer"), description: "Array of selected presentation IDs"),
        new OA\Property(property: "interested_presentations", type: "array", items: new OA\Items(type: "integer"), description: "Array of interested presentation IDs (only for Individual lists)", nullable: true),
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
