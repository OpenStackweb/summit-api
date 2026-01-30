<?php

namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'PresentationSlide',
    type: 'object',
    description: 'Represents a slide attachment for a presentation',
    properties: [
        // Base fields (from SilverStripeSerializer)
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'created', type: 'integer', description: 'Unix timestamp', example: 1640995200),
        new OA\Property(property: 'last_edited', type: 'integer', description: 'Unix timestamp', example: 1640995200),

        // PresentationMaterial fields
        new OA\Property(property: 'name', type: 'string', example: 'Introduction Slides'),
        new OA\Property(property: 'description', type: 'string', nullable: true, example: 'Overview slides for the presentation'),
        new OA\Property(property: 'display_on_site', type: 'boolean', example: true),
        new OA\Property(property: 'featured', type: 'boolean', example: false),
        new OA\Property(property: 'order', type: 'integer', example: 1),
        new OA\Property(property: 'presentation_id', type: 'integer', example: 123, description: 'Presentation ID'),
        new OA\Property(property: 'class_name', type: 'string', example: 'PresentationSlide'),

        // PresentationSlide-specific fields
        new OA\Property(property: 'link', type: 'string', format: 'uri', nullable: true, example: 'https://example.com/slides/presentation.pdf', description: 'URL to the slide file or external link'),
        new OA\Property(property: 'has_file', type: 'boolean', example: true, description: 'Whether the slide has an uploaded file'),
    ]
)]
class PresentationSlideSchema {}
