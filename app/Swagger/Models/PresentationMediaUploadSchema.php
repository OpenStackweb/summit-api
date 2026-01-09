<?php

namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'PresentationMediaUpload',
    type: 'object',
    description: 'Represents a media file uploaded for a presentation',
    properties: [
        // Base fields (from SilverStripeSerializer)
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'created', type: 'integer', description: 'Unix timestamp', example: 1640995200),
        new OA\Property(property: 'last_edited', type: 'integer', description: 'Unix timestamp', example: 1640995200),

        // PresentationMaterial fields (some are overridden)
        new OA\Property(property: 'name', type: 'string', nullable: true, example: 'Speaker Photo', description: 'Derived from media upload type name'),
        new OA\Property(property: 'description', type: 'string', nullable: true, example: 'Photo of the speaker', description: 'Derived from media upload type description'),
        new OA\Property(property: 'display_on_site', type: 'boolean', example: true),
        new OA\Property(property: 'order', type: 'integer', example: 1),
        new OA\Property(property: 'presentation_id', type: 'integer', example: 123, description: 'Presentation ID'),
        new OA\Property(property: 'class_name', type: 'string', example: 'PresentationMediaUpload'),

        // PresentationMediaUpload-specific fields
        new OA\Property(property: 'filename', type: 'string', example: 'speaker-photo.jpg', description: 'Name of the uploaded file'),
        new OA\Property(property: 'media_upload_type_id', type: 'integer', example: 5, description: 'SummitMediaUploadType ID'),
        new OA\Property(property: 'public_url', type: 'string', format: 'uri', nullable: true, example: 'https://storage.example.com/uploads/speaker-photo.jpg', description: 'Public URL to access the uploaded file'),

        // Expandable relations
        new OA\Property(property: 'media_upload_type', description: 'SummitMediaUploadType object, expanded when using expand=media_upload_type'),
    ]
)]
class PresentationMediaUploadSchema {}
