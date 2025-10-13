<?php

namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;

// Badge Print Schemas

#[OA\Schema(
    schema: "SummitAttendeeBadgePrint",
    description: "Summit Attendee Badge Print",
    properties: [
        new OA\Property(property: "id", type: "integer", example: 1),
        new OA\Property(property: "created", type: "integer", format: "int64", description: "Creation timestamp (epoch)", example: 1234567890),
        new OA\Property(property: "last_edited", type: "integer", format: "int64", description: "Last edit timestamp (epoch)", example: 1234567890),
        new OA\Property(property: "print_date", type: "integer", format: "int64", description: "Print timestamp (epoch)", example: 1234567890),
        new OA\Property(property: "requestor_id", type: "integer", description: "ID of the member who requested the print", example: 123),
        new OA\Property(property: "badge_id", type: "integer", description: "ID of the badge that was printed", example: 456),
        new OA\Property(property: "view_type_id", type: "integer", description: "ID of the badge view type used for printing", example: 789),
        new OA\Property(property: "view_type_name", type: "string", description: "Name of the badge view type", example: "Standard Badge"),
    ],
    type: "object"
)]
class SummitAttendeeBadgePrintSchema
{
}

#[OA\Schema(
    schema: "PaginatedSummitAttendeeBadgePrintsResponse",
    description: "Paginated response for Summit Attendee Badge Prints",
    properties: [
        new OA\Property(property: "total", type: "integer", example: 100),
        new OA\Property(property: "per_page", type: "integer", example: 15),
        new OA\Property(property: "current_page", type: "integer", example: 1),
        new OA\Property(property: "last_page", type: "integer", example: 7),
        new OA\Property(
            property: "data",
            type: "array",
            items: new OA\Items(ref: "#/components/schemas/SummitAttendeeBadgePrint")
        ),
    ],
    type: "object"
)]
class PaginatedSummitAttendeeBadgePrintsResponseSchema
{
}
