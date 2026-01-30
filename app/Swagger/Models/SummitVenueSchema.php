<?php

namespace App\Swagger\Models;

use OpenApi\Attributes as OA;

/**
 * Schema for SummitVenue model
 * Extends SummitGeoLocatedLocation with venue-specific properties
 */
#[OA\Schema(
    schema: 'SummitVenue',
    type: 'object',
    description: 'Summit venue with rooms and floors',
    allOf: [
        new OA\Schema(ref: '#/components/schemas/SummitGeoLocatedLocation'),
        new OA\Schema(
            type: 'object',
            properties: [
                new OA\Property(property: 'is_main', type: 'boolean', description: 'Whether this is the main venue'),
                new OA\Property(
                    property: 'rooms',
                    type: 'array',
                    items: new OA\Items(type: 'integer'),
                    description: 'Array of venue room IDs'
                ),
                new OA\Property(
                    property: 'floors',
                    type: 'array',
                    items: new OA\Items(type: 'integer'),
                    description: 'Array of venue floor IDs'
                ),
            ]
        )
    ]
)]
class SummitVenueSchema {}
