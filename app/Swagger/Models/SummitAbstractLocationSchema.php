<?php

namespace App\Swagger\Models;

use OpenApi\Attributes as OA;

/**
 * Schema for SummitAbstractLocation model
 * Base schema for all location types
 */
#[OA\Schema(
    schema: 'SummitAbstractLocation',
    type: 'object',
    description: 'Base location object for summits',
    properties: [
        new OA\Property(property: 'id', type: 'integer', description: 'Unique identifier'),
        new OA\Property(property: 'created', type: 'integer', description: 'Creation timestamp (epoch)'),
        new OA\Property(property: 'last_edited', type: 'integer', description: 'Last modification timestamp (epoch)'),
        new OA\Property(property: 'name', type: 'string', description: 'Location name'),
        new OA\Property(property: 'short_name', type: 'string', description: 'Short name for the location'),
        new OA\Property(property: 'description', type: 'string', description: 'Location description'),
        new OA\Property(property: 'location_type', type: 'string', description: 'Type of location'),
        new OA\Property(property: 'order', type: 'integer', description: 'Display order'),
        new OA\Property(property: 'opening_hour', type: 'integer', description: 'Opening hour (0-23)'),
        new OA\Property(property: 'closing_hour', type: 'integer', description: 'Closing hour (0-23)'),
        new OA\Property(property: 'class_name', type: 'string', description: 'Class name identifier'),
        new OA\Property(
            property: 'published_events',
            type: 'array',
            items: new OA\Items(type: 'integer'),
            description: 'Array of published event IDs at this location'
        ),
    ]
)]
class SummitAbstractLocationSchema {}
