<?php
 namespace App\Swagger\Elections;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "ElectionsList",
    type: "object",
    properties: [
        new OA\Property(
            property: "data",
            type: "array",
            items: new OA\Items(ref: "#/components/schemas/Election")
        ),
        new OA\Property(
            property: "total",
            type: "integer",
            example: 10
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
class ElectionsListSchema {}