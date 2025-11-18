<?php
namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;




#[OA\Schema(
    schema: 'CandidateProfileUpdateRequest',
    type: 'object',
    properties: [
        new OA\Property(property: 'bio', type: 'string', description: 'Candidate biography'),
        new OA\Property(property: 'relationship_to_openstack', type: 'string', description: 'Relationship to OpenStack'),
        new OA\Property(property: 'experience', type: 'string', description: 'Professional experience'),
        new OA\Property(property: 'boards_role', type: 'string', description: 'Board role experience'),
        new OA\Property(property: 'top_priority', type: 'string', description: 'Top priority if elected'),
    ]
)]
class CandidateProfileUpdateRequestSchema {}

#[OA\Schema(
    schema: 'NominationRequest',
    type: 'object',
    properties: [
        new OA\Property(property: 'comment', type: 'string', nullable: true, description: 'Optional comment for the nomination'),
    ]
)]
class NominationRequestSchema {}


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