<?php

namespace App\Swagger\Models;

use OpenApi\Attributes as OA;

/**
 * Schema for SummitHotel model
 * Extends SummitExternalLocation with hotel-specific properties
 */
#[OA\Schema(
    schema: 'SummitHotel',
    type: 'object',
    description: 'Summit hotel',
    allOf: [
        new OA\Schema(ref: '#/components/schemas/SummitExternalLocation'),
        new OA\Schema(
            type: 'object',
            properties: [
                new OA\Property(property: 'booking_link', type: 'string', format: 'uri', description: 'Booking URL'),
                new OA\Property(property: 'hotel_type', type: 'string', description: 'Type of hotel (Primary, Alternate)'),
                new OA\Property(property: 'sold_out', type: 'boolean', description: 'Whether the hotel is sold out'),
            ]
        )
    ]
)]
class SummitHotelSchema {}
