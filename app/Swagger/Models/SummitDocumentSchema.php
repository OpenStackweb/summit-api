<?php
namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;


#[OA\Schema(
    schema: "SummitDocument",
    description: "Summit document",
    type: "object",
    properties: [
        new OA\Property(property: "id", type: "integer", example: 1),
        new OA\Property(property: "created", type: "integer", description: "Unix timestamp", example: 1640995200),
        new OA\Property(property: "last_edited", type: "integer", description: "Unix timestamp", example: 1640995200),
        new OA\Property(property: "name", type: "string", example: "Code of Conduct"),
        new OA\Property(property: "description", type: "string", example: "Summit code of conduct document"),
        new OA\Property(property: "show_always", type: "boolean", example: true),
        new OA\Property(property: "label", type: "string", example: "Code of Conduct"),
        new OA\Property(property: "file", type: "string", format: "uri", nullable: true, example: "https://example.com/document.pdf"),
        new OA\Property(property: "web_link", type: "string", format: "uri", nullable: true, example: "https://example.com/page"),
        new OA\Property(property: "selection_plan_id", type: "integer", nullable: true, description: "SelectionPlan ID, full object description when ?expand=summit (summit)"),
        new OA\Property(
            property: "event_types",
            type: "array",
            items: new OA\Items(type: "integer"),
            description: "Array of SummitEventType: objects when expanded, ids otherwise",
        ),
        new OA\Property(property: "summit_id", type: "integer", description: "Summit ID, full object description when ?expand=summit (summit)"),
    ]
)]
class SummitDocumentSchema {}
