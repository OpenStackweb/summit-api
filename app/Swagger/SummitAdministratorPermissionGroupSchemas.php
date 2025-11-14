<?php 
namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "SummitAdministratorPermissionGroup",
    type: "object",
    properties: [
        new OA\Property(property: "id", type: "integer"),
        new OA\Property(property: "title", type: "string"),
        new OA\Property(property: "created", type: "integer"),
        new OA\Property(property: "last_edited", type: "integer"),
        new OA\Property(
            property: "members",
            description: "Array of member IDs. Use expand=members to get full Member objects",
            oneOf: [
                new OA\Schema(type: "array", items: new OA\Items(type: "integer")),
                new OA\Schema(type: "array", items: new OA\Items(type: "object", description: "Member"))
            ]
        ),
        new OA\Property(
            property: "summits",
            description: "Array of summit IDs. Use expand=summits to get full Summit objects",
            oneOf: [
                new OA\Schema(type: "array", items: new OA\Items(type: "integer")),
                new OA\Schema(type: "array", items: new OA\Items(type: "object", description: "Summit"))
            ]
        ),
    ]
)]
class SummitAdministratorPermissionGroupSchemas
{
}

#[OA\Schema(
    schema: "CreateSummitAdministratorPermissionGroup",
    type: "object",
    required: ["title", "summits", "members"],
    properties: [
        new OA\Property(property: "title", type: "string", description: "Group title"),
        new OA\Property(property: "summits", type: "array", items: new OA\Items(type: "integer"), description: "Array of summit IDs"),
        new OA\Property(property: "members", type: "array", items: new OA\Items(type: "integer"), description: "Array of member IDs"),
    ]
)]
class CreateSummitAdministratorPermissionGroupSchema
{
}

#[OA\Schema(
    schema: "UpdateSummitAdministratorPermissionGroup",
    type: "object",
    properties: [
        new OA\Property(property: "title", type: "string", description: "Group title"),
        new OA\Property(property: "summits", type: "array", items: new OA\Items(type: "integer"), description: "Array of summit IDs"),
        new OA\Property(property: "members", type: "array", items: new OA\Items(type: "integer"), description: "Array of member IDs"),
    ]
)]
class UpdateSummitAdministratorPermissionGroupSchema
{
}

#[OA\Schema(
    schema: "SummitAdministratorPermissionGroupList",
    type: "object",
    allOf: [
        new OA\Schema(ref: "#/components/schemas/PaginateDataSchemaResponse"),
        new OA\Schema(
            type: "object",
            properties: [
                new OA\Property(
                    property: "data",
                    type: "array",
                    items: new OA\Items(ref: "#/components/schemas/SummitAdministratorPermissionGroup")
                )
            ]
        )
    ]
)]
class SummitAdministratorPermissionGroupListSchema
{
}
