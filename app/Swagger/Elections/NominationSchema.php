<?php
 namespace App\Swagger\Elections;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "Nomination",
    type: "object",
    required: ["id"],
    properties: [
        new OA\Property(
            property: "id",
            type: "integer",
            format: "int64",
            example: 1
        ),
        new OA\Property(
            property: "election_id",
            type: "integer",
            format: "int64",
            example: 1
        ),
        new OA\Property(
            property: "candidate_id",
            type: "integer",
            format: "int64",
            example: 123
        ),
        new OA\Property(
            property: "nominator_id",
            type: "integer",
            format: "int64",
            example: 456
        ),
        new OA\Property(
            property: "comment",
            type: "string",
            nullable: true,
            example: "Great candidate for the board"
        ),
        new OA\Property(
            property: "created_at",
            type: "integer",
            format: "int64"
        ),
        new OA\Property(
            property: "updated_at",
            type: "integer",
            format: "int64"
        ),
    ]
)]
class NominationSchema {}