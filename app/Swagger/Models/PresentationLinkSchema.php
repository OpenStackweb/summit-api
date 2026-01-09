<?php

namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'PresentationLink',
    type: 'object',
    description: 'Represents an external link associated with a presentation',
    properties: [
        // Base fields (from SilverStripeSerializer)
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'created', type: 'integer', description: 'Unix timestamp', example: 1640995200),
        new OA\Property(property: 'last_edited', type: 'integer', description: 'Unix timestamp', example: 1640995200),

        // PresentationMaterial fields
        new OA\Property(property: 'name', type: 'string', example: 'Related Documentation'),
        new OA\Property(property: 'description', type: 'string', nullable: true, example: 'Link to the project documentation'),
        new OA\Property(property: 'display_on_site', type: 'boolean', example: true),
        new OA\Property(property: 'featured', type: 'boolean', example: false),
        new OA\Property(property: 'order', type: 'integer', example: 1),
        new OA\Property(property: 'presentation_id', type: 'integer', example: 123, description: 'Presentation ID'),
        new OA\Property(property: 'class_name', type: 'string', example: 'PresentationLink'),

        // PresentationLink-specific fields
        new OA\Property(property: 'link', type: 'string', format: 'uri', nullable: true, example: 'https://docs.example.com/project', description: 'External URL'),
    ]
)]
class PresentationLinkSchema {}
