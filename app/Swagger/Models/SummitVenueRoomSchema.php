<?php

namespace App\Swagger\Models;

use OpenApi\Attributes as OA;

/**
 * Schema for SummitVenueRoom model
 * Extends SummitAbstractLocation with room-specific properties
 */
#[OA\Schema(
    schema: 'SummitVenueRoom',
    type: 'object',
    description: 'Summit venue room',
    allOf: [
        new OA\Schema(ref: '#/components/schemas/SummitAbstractLocation'),
        new OA\Schema(
            type: 'object',
            properties: [
                new OA\Property(property: 'venue_id', type: 'integer', description: 'ID of the parent venue'),
                new OA\Property(property: 'floor_id', type: 'integer', nullable: true, description: 'ID of the floor (if applicable)'),
                new OA\Property(property: 'capacity', type: 'integer', description: 'Room capacity'),
                new OA\Property(property: 'override_blackouts', type: 'boolean', description: 'Whether to override blackout periods'),
                new OA\Property(
                    property: 'image',
                    ref: '#/components/schemas/SummitImage',
                    nullable: true,
                    description: 'Room image'
                ),
                new OA\Property(
                    property: 'attributes',
                    type: 'array',
                    items: new OA\Items(type: 'integer'),
                    description: 'Array of attribute IDs'
                ),
            ]
        )
    ]
)]
class SummitVenueRoomSchema {}
