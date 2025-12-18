<?php

namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'SponsorMaterial',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'sponsor_id', type: 'integer', example: 1),
        new OA\Property(property: 'type', type: 'string', example: 'Presentation', enum: ['Presentation', 'Demo', 'Handout', 'Other']),
        new OA\Property(property: 'name', type: 'string', example: 'Material Name'),
        new OA\Property(property: 'description', type: 'string', example: 'Material description', nullable: true),
        new OA\Property(property: 'order', type: 'integer', example: 1),
        new OA\Property(property: 'file', ref: '#/components/schemas/File', nullable: true),
    ]
)]
class SponsorMaterialSchema {}
