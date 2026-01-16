<?php

namespace App\Swagger\Models;

use OpenApi\Attributes as OA;

/**
 * Schema for SummitAirport model
 * Extends SummitExternalLocation with airport-specific properties
 */
#[OA\Schema(
    schema: 'SummitAirport',
    type: 'object',
    description: 'Summit airport',
    allOf: [
        new OA\Schema(ref: '#/components/schemas/SummitExternalLocation'),
        new OA\Schema(
            type: 'object',
            properties: [
                new OA\Property(property: 'airport_type', type: 'string', description: 'Type of airport (Primary, Alternate)'),
            ]
        )
    ]
)]
class SummitAirportSchema {}
