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
                new OA\Schema(type: "array", items: new OA\Items(ref: "#/components/schemas/Member"))
            ]
        ),
        new OA\Property(
            property: "summits",
            description: "Array of summit IDs. Use expand=summits to get full Summit objects",
            oneOf: [
                new OA\Schema(type: "array", items: new OA\Items(type: "integer")),
                new OA\Schema(type: "array", items: new OA\Items(ref: "#/components/schemas/Summit"))
            ]
        ),
    ]
)]
class SummitAdministratorPermissionGroupSchemas
{
}

