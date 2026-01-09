<?php

namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'PresentationVideo',
    type: 'object',
    description: 'Represents a video associated with a presentation',
    properties: [
        // Base fields (from SilverStripeSerializer)
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'created', type: 'integer', description: 'Unix timestamp', example: 1640995200),
        new OA\Property(property: 'last_edited', type: 'integer', description: 'Unix timestamp', example: 1640995200),

        // PresentationMaterial fields
        new OA\Property(property: 'name', type: 'string', example: 'Presentation Recording'),
        new OA\Property(property: 'description', type: 'string', nullable: true, example: 'Full recording of the presentation'),
        new OA\Property(property: 'display_on_site', type: 'boolean', example: true),
        new OA\Property(property: 'featured', type: 'boolean', example: false),
        new OA\Property(property: 'order', type: 'integer', example: 1),
        new OA\Property(property: 'presentation_id', type: 'integer', example: 123, description: 'Presentation ID'),
        new OA\Property(property: 'class_name', type: 'string', example: 'PresentationVideo'),

        // PresentationVideo-specific fields
        new OA\Property(property: 'youtube_id', type: 'string', nullable: true, example: 'dQw4w9WgXcQ', description: 'YouTube video ID'),
        new OA\Property(property: 'external_url', type: 'string', format: 'uri', nullable: true, example: 'https://vimeo.com/123456789', description: 'External video URL'),
        new OA\Property(property: 'data_uploaded', type: 'integer', description: 'Unix timestamp when video was uploaded', nullable: true, example: 1640995200),
        new OA\Property(property: 'highlighted', type: 'boolean', example: false, description: 'Whether the video is highlighted'),
        new OA\Property(property: 'views', type: 'integer', example: 1500, description: 'Number of video views'),
    ]
)]
class PresentationVideoSchema {}
