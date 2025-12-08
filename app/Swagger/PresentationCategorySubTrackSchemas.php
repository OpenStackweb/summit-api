<?php
namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "PresentationCategorySubTrack",
    description: "Sub-Track for Parent Track",
    type: "object",
    properties: [
        new OA\Property(property: "id", description: "Track ID", type: "integer", format: "int64"),
        new OA\Property(property: "name", description: "Track Name", type: "string"),
        new OA\Property(property: "code", description: "Track Code", type: "string"),
        new OA\Property(property: "order", description: "Display Order", type: "integer"),
    ]
)]
class PresentationCategorySubTrackSchemas {}