<?php

namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "Tag",
    type: "object",
    required: ["id", "tag"],
    properties: [
        new OA\Property(
            property: "id",
            type: "integer",
            format: "int64",
            description: "Tag ID",
            example: 1
        ),
        new OA\Property(
            property: "tag",
            type: "string",
            description: "Tag name/value",
            example: "Beginner"
        ),
    ]
)]
class TagSchema {}
