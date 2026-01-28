<?php

namespace App\Swagger\Models;

use OpenApi\Attributes as OA;

/**
 * Schema for SummitBookableVenueRoom model
 * Extends SummitVenueRoom with booking-specific properties
 */
#[OA\Schema(
    schema: 'SummitBookableVenueRoom',
    type: 'object',
    description: 'Summit bookable venue room',
    allOf: [
        new OA\Schema(ref: '#/components/schemas/SummitVenueRoom'),
        new OA\Schema(
            type: 'object',
            properties: [
                new OA\Property(property: 'time_slot_cost', type: 'number', format: 'float', description: 'Cost per time slot'),
                new OA\Property(property: 'currency', type: 'string', description: 'Currency code (e.g., USD)'),
                new OA\Property(
                    property: 'attribute_values',
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/SummitBookableVenueRoomAttributeValue'),
                    description: 'Attribute values for this room'
                ),
            ]
        )
    ]
)]
class SummitBookableVenueRoomSchema {}
