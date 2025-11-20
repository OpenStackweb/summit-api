<?php 
namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "SummitDocument",
    type: "object",
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
            description: "Document name",
            example: "Presentation Guidelines"
        ),
        new OA\Property(
            property: "description",
            type: "string",
            nullable: true,
            description: "Document description",
            example: "Guidelines for presenters"
        ),
        new OA\Property(
            property: "show_always",
            type: "boolean",
            description: "Whether document is always shown",
            example: true
        ),
        new OA\Property(
            property: "label",
            type: "string",
            nullable: true,
            description: "Document label",
            example: "Guidelines"
        ),
        new OA\Property(
            property: "summit_id",
            type: "integer",
            format: "int64",
            example: 1
        ),
        new OA\Property(
            property: "file",
            type: "string",
            format: "url",
            nullable: true,
            description: "File URL",
            example: "https://example.com/documents/guidelines.pdf"
        ),
        new OA\Property(
            property: "selection_plan_id",
            type: "integer",
            format: "int64",
            nullable: true,
            description: "Selection plan ID if associated"
        ),
        new OA\Property(
            property: "web_link",
            type: "string",
            format: "url",
            nullable: true,
            description: "Web link URL",
            example: "https://example.com/guidelines"
        ),
        new OA\Property(
            property: "created",
            type: "integer",
            format: "int64"
        ),
        new OA\Property(
            property: "last_edited",
            type: "integer",
            format: "int64"
        ),
    ]
)]
class SummitDocumentSchema {}
