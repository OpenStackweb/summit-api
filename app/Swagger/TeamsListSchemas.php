<?php
namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "TeamsList",
    type: "object",
    properties: [
        new OA\Property(
            property: "data",
            type: "array",
            items: new OA\Items(ref: "#/components/schemas/Team")
        ),
        new OA\Property(
            property: "total",
            type: "integer",
            example: 5
        ),
        new OA\Property(
            property: "per_page",
            type: "integer",
            example: 20
        ),
        new OA\Property(
            property: "current_page",
            type: "integer",
            example: 1
        ),
    ]
)]
class TeamsListSchema {}