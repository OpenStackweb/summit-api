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
    schema: "PaginatedElectionsResponse",
    allOf: [
        new OA\Schema(ref: '#/components/schemas/PaginateDataSchemaResponse'),
        new OA\Schema(
            type: 'object',
            properties: [
                new OA\Property(
                    property: "data",
                    type: "array",
                    items: new OA\Items(ref: "#/components/schemas/Election")
                )
            ]
        )]
)]
class PaginatedElectionsResponseSchema
{
}


#[OA\Schema(
    schema: "PaginatedCandidatesResponse",
    allOf: [
        new OA\Schema(ref: '#/components/schemas/PaginateDataSchemaResponse'),
        new OA\Schema(
            type: 'object',
            properties: [
                new OA\Property(
                    property: "data",
                    type: "array",
                    items: new OA\Items(ref: "#/components/schemas/Candidate")
                )
            ]
        )]
)]
class PaginatedCandidatesResponseSchema
{
}
