<?php
namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;


#[OA\Schema(
    schema: "Nomination",
    type: "object",
    required: ["id", "candidate_id", "nominator_id", "election_id"],
    properties: [
        new OA\Property(
            property: "id",
            type: "integer",
            format: "int64",
            description: "Nomination ID",
            example: 1
        ),
        new OA\Property(
            property: "election_id",
            type: "integer",
            format: "int64",
            description: "Associated election ID",
            example: 1
        ),
        new OA\Property(
            property: "candidate_id",
            type: "integer",
            format: "int64",
            description: "Nominated candidate ID",
            example: 123
        ),
        new OA\Property(
            property: "nominator_id",
            type: "integer",
            format: "int64",
            description: "Member who made the nomination",
            example: 456
        ),
        new OA\Property(
            property: "comment",
            type: "string",
            nullable: true,
            description: "Optional nomination comment",
            example: "Great candidate for the board"
        ),
        new OA\Property(
            property: "created_at",
            type: "integer",
            format: "int64",
            description: "Creation timestamp (unix epoch)"
        ),
        new OA\Property(
            property: "updated_at",
            type: "integer",
            format: "int64",
            description: "Last update timestamp (unix epoch)"
        ),
        new OA\Property(
            property: "election",
            description: "Expanded election object (only with expand=election)",
            ref: "#/components/schemas/Election",
            nullable: true
        ),
        new OA\Property(
            property: "candidate",
            description: "Expanded candidate object (only with expand=candidate)",
            ref: "#/components/schemas/Candidate",
            nullable: true
        ),
        new OA\Property(
            property: "nominator",
            description: "Expanded nominator member object (only with expand=nominator)",
            ref: "#/components/schemas/Member",
            nullable: true
        ),
    ]
)]
class NominationSchema {}