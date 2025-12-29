<?php

namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "Tag",
    type: "object",
    required: ["id", "tag"],
    properties: [
        new OA\Property(property: "id", type: "integer", example: 1),
        new OA\Property(property: "created", type: "integer", format: "int64", description: "Creation timestamp (epoch)", example: 1234567890),
        new OA\Property(property: "last_edited", type: "integer", format: "int64", description: "Last edit timestamp (epoch)", example: 1234567890),
        new OA\Property(property: "tag", type: "string", maxLength: 100, example: "Cloud Computing"),
    ]
)]
class TagSchema {}
