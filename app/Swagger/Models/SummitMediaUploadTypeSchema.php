<?php

namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;



#[OA\Schema(
    schema: "SummitMediaUploadType",
    description: "Summit Media Upload Type",
    type: "object",
    properties: [
        new OA\Property(property: "id", type: "integer", example: 1),
        new OA\Property(property: "created", type: "integer", format: "int64", description: "Creation timestamp (epoch)", example: 1234567890),
        new OA\Property(property: "last_edited", type: "integer", format: "int64", description: "Last edit timestamp (epoch)", example: 1234567890),
        new OA\Property(property: "name", type: "string", maxLength: 255, example: "Speaker Photo"),
        new OA\Property(property: "description", type: "string", maxLength: 5120, nullable: true, example: "High resolution photo of the speaker"),
        new OA\Property(property: "max_size", type: "integer", description: "Maximum file size in KB", example: 10240),
        new OA\Property(property: "is_mandatory", type: "boolean", example: true),
        new OA\Property(property: "min_uploads_qty", type: "integer", minimum: 0, example: 1),
        new OA\Property(property: "max_uploads_qty", type: "integer", minimum: 0, example: 1),
        new OA\Property(property: "use_temporary_links_on_public_storage", type: "boolean", example: false),
        new OA\Property(property: "temporary_links_public_storage_ttl", type: "integer", description: "TTL in seconds", nullable: true, example: 3600),
        new OA\Property(property: "private_storage_type", type: "string", example: "local"),
        new OA\Property(property: "public_storage_type", type: "string", example: "s3"),
        new OA\Property(property: "is_editable", type: "boolean", example: true),
        new OA\Property(property: "type_id", type: "integer", example: 456),
        new OA\Property(property: "type", ref: "#/components/schemas/SummitMediaFileType", description: "Only present when relations=presentation_types and expand includes 'type' in it."),
        new OA\Property(property: "summit_id", type: "integer", example: 123, description: "Summit ID, only when expand does NOT include 'summit' in it."),
        new OA\Property(property: "summit", ref: "#/components/schemas/Summit", description: "Summit expand (only when relations=presentation_types) and expand includes 'summit' in it."),
        new OA\Property(
            property: "presentation_types",
            type: "array",
            items: new OA\Items(type: "integer"),
            description: "Array of PresentationType IDs when relations=presentation_types and full objects when ?expand=presentation_types is used",
        ),
    ],
)]
class SummitMediaUploadTypeSchema
{
}
