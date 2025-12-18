<?php

namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'SponsorAd',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'sponsor_id', type: 'integer', example: 1),
        new OA\Property(property: 'text', type: 'string', example: 'Ad text'),
        new OA\Property(property: 'alt', type: 'string', example: 'Alt text for image'),
        new OA\Property(property: 'link', type: 'string', example: 'https://example.com'),
        new OA\Property(property: 'order', type: 'integer', example: 1),
        new OA\Property(property: 'image', ref: '#/components/schemas/File', nullable: true),
    ]
)]
class SponsorAdSchema {}
