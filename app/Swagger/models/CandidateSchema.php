<?php
namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "Candidate",
    type: "object",
    required: ["id", "member_id", "election_id"],
    properties: [
        new OA\Property(
            property: "id",
            type: "integer",
            format: "int64",
            description: "Candidate ID",
            example: 1
        ),
        new OA\Property(
            property: "member_id",
            type: "integer",
            format: "int64",
            description: "Associated member ID",
            example: 123
        ),
        new OA\Property(
            property: "election_id",
            type: "integer",
            format: "int64",
            description: "Associated election ID",
            example: 1
        ),
        new OA\Property(
            property: "has_accepted_nomination",
            type: "boolean",
            description: "Whether candidate has accepted nomination",
            example: true
        ),
        new OA\Property(
            property: "is_gold_member",
            type: "boolean",
            description: "Whether candidate is featured/gold",
            example: false
        ),
        new OA\Property(
            property: "relationship_to_openstack",
            type: "string",
            nullable: true,
            description: "Relationship to OpenStack",
            example: "Core contributor"
        ),
        new OA\Property(
            property: "experience",
            type: "string",
            nullable: true,
            description: "Professional experience",
            example: "10 years in open source"
        ),
        new OA\Property(
            property: "boards_role",
            type: "string",
            nullable: true,
            description: "Board role experience",
            example: "Board member for 3 years"
        ),
        new OA\Property(
            property: "bio",
            type: "string",
            nullable: true,
            description: "Candidate biography",
            example: "Passionate OpenStack contributor"
        ),
        new OA\Property(
            property: "top_priority",
            type: "string",
            nullable: true,
            description: "Top priority if elected",
            example: "Improve community engagement"
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
            property: "member",
            description: "Member details (only when expand=member)",
            ref: "#/components/schemas/Member",
            nullable: true
        ),
        new OA\Property(
            property: "election",
            description: "Election details (only when expand=election)",
            ref: "#/components/schemas/Election",
            nullable: true
        ),
    ]
)]
class CandidateSchema {}