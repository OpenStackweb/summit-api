<?php

namespace App\Swagger\Models;

use OpenApi\Attributes as OA;

/**
 * Schema for SummitVenueFloor model
 */
#[OA\Schema(
    schema: 'SummitVenueFloor',
    type: 'object',
    description: 'Summit venue floor',
    properties: [
        new OA\Property(property: 'id', type: 'integer', description: 'Unique identifier'),
        new OA\Property(property: 'created', type: 'integer', format: 'int64', description: 'Creation timestamp (epoch)'),
        new OA\Property(property: 'last_edited', type: 'integer', format: 'int64', description: 'Last modification timestamp (epoch)'),
        new OA\Property(property: 'name', type: 'string', description: 'Floor name'),
        new OA\Property(property: 'description', type: 'string', description: 'Floor description'),
        new OA\Property(property: 'number', type: 'integer', description: 'Floor number'),
        new OA\Property(property: 'venue_id', type: 'integer', description: 'ID of the parent venue'),
        new OA\Property(
            property: 'image',
            ref: '#/components/schemas/SummitImage',
            nullable: true,
            description: 'Floor image'
        ),
        new OA\Property(
            property: 'rooms',
            type: 'array',
            items: new OA\Items(type: 'integer'),
            description: 'Array of room IDs on this floor'
        ),
    ]
)]
class SummitVenueFloorSchema {}
