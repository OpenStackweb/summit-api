<?php
namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "PresentationCategoryExtraQuestion",
    description: "Extra Question for Track",
    type: "object",
    properties: [
        new OA\Property(property: "id", description: "Question ID", type: "integer", format: "int64"),
        new OA\Property(property: "name", description: "Question Name", type: "string"),
        new OA\Property(property: "type", description: "Question Type", type: "string", enum: ["text", "textarea", "dropdown", "checkbox", "radio"]),
        new OA\Property(property: "label", description: "Question Label", type: "string"),
        new OA\Property(property: "is_mandatory", description: "Is Mandatory", type: "boolean"),
        new OA\Property(property: "order", description: "Display Order", type: "integer"),
    ]
)]
class PresentationCategoryExtraQuestionSchemas {}