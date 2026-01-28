<?php

namespace App\Swagger\Models;

use OpenApi\Attributes as OA;

/**
 * Schema for SummitExternalLocation model
 * Extends SummitGeoLocatedLocation with external location-specific properties
 */
#[OA\Schema(
    schema: 'SummitExternalLocation',
    type: 'object',
    description: 'Summit external location',
    allOf: [
        new OA\Schema(ref: '#/components/schemas/SummitGeoLocatedLocation'),
        new OA\Schema(
            type: 'object',
            properties: [
                new OA\Property(property: 'capacity', type: 'integer', description: 'Location capacity'),
            ]
        )
    ]
)]
class SummitExternalLocationSchema {}
