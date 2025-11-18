<?php
namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "Election",
    type: "object",
    required: ["id", "name"],
    properties: [
        new OA\Property(
            property: "id",
            type: "integer",
            format: "int64",
            example: 1
        ),
        new OA\Property(
            property: "name",
            type: "string",
            example: "Board Election 2025"
        ),
        new OA\Property(
            property: "status",
            type: "string",
            enum: ["open", "closed", "upcoming"],
            example: "open"
        ),
        new OA\Property(
            property: "opens",
            type: "integer",
            format: "int64",
            description: "Unix timestamp when election opens",
            example: 1634567890
        ),
        new OA\Property(
            property: "closes",
            type: "integer",
            format: "int64",
            description: "Unix timestamp when election closes",
            example: 1634654290
        ),
        new OA\Property(
            property: "nomination_opens",
            type: "integer",
            format: "int64",
            description: "Unix timestamp when nominations open",
            example: 1634481490
        ),
        new OA\Property(
            property: "nomination_closes",
            type: "integer",
            format: "int64",
            description: "Unix timestamp when nominations close",
            example: 1634567890
        ),
        new OA\Property(
            property: "nomination_application_deadline",
            type: "integer",
            format: "int64",
            description: "Unix timestamp for nomination application deadline",
            example: 1634567890
        ),
        new OA\Property(
            property: "candidate_application_form_relationship_to_openstack_label",
            type: "string",
            example: "Relationship to OpenStack"
        ),
        new OA\Property(
            property: "candidate_application_form_experience_label",
            type: "string",
            example: "Experience in OpenStack"
        ),
        new OA\Property(
            property: "candidate_application_form_boards_role_label",
            type: "string",
            example: "Board Role Experience"
        ),
        new OA\Property(
            property: "candidate_application_form_top_priority_label",
            type: "string",
            example: "Top Priorities"
        ),
        new OA\Property(
            property: "nominations_limit",
            type: "integer",
            example: 10
        ),
        new OA\Property(
            property: "created_at",
            type: "integer",
            format: "int64",
            description: "Creation timestamp"
        ),
        new OA\Property(
            property: "updated_at",
            type: "integer",
            format: "int64",
            description: "Last update timestamp"
        ),
    ]
)]
class ElectionSchema {}