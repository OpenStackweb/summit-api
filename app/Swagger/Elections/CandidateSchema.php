<?php
namespace App\Swagger\Elections;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "Candidate",
    type: "object",
    required: ["id", "member_id"],
    properties: [
        new OA\Property(
            property: "id",
            type: "integer",
            format: "int64",
            example: 1
        ),
        new OA\Property(
            property: "member_id",
            type: "integer",
            format: "int64",
            example: 123
        ),
        new OA\Property(
            property: "member_email",
            type: "string",
            format: "email",
            example: "candidate@example.com"
        ),
        new OA\Property(
            property: "member_name",
            type: "string",
            example: "John Doe"
        ),
        new OA\Property(
            property: "election_id",
            type: "integer",
            format: "int64",
            example: 1
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
class CandidateSchema {}