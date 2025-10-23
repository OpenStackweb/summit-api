<?php
namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "PresentationCategoryAllowedTag",
    description: "Allowed Tag for Track",
    type: "object",
    properties: [
        new OA\Property(property: "id", description: "Tag ID", type: "integer", format: "int64"),
        new OA\Property(property: "tag", description: "Tag Name", type: "string"),
        new OA\Property(
            property: "track_tag_group",
            description: "Track Tag Group (see TrackTagGroup schema)",
            type: "object",
            nullable: true
        ),
    ]
)]
class PresentationCategoryAllowedTagSchemas {}