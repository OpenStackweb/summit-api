<?php

namespace App\Swagger\Models;

use OpenApi\Attributes as OA;

/**
 * Schema for SummitLocationMap model
 */
#[OA\Schema(
    schema: 'SummitLocationMap',
    type: 'object',
    description: 'Summit location map',
    properties: [
        new OA\Property(property: 'id', type: 'integer', description: 'Unique identifier'),
        new OA\Property(property: 'created', type: 'integer', format: 'int64', description: 'Creation timestamp (epoch)'),
        new OA\Property(property: 'last_edited', type: 'integer', format: 'int64', description: 'Last modification timestamp (epoch)'),
        new OA\Property(property: 'name', type: 'string', description: 'Map name'),
        new OA\Property(property: 'description', type: 'string', description: 'Map description'),
        new OA\Property(property: 'class_name', type: 'string', description: 'Class type'),
        new OA\Property(property: 'location_id', type: 'integer', description: 'ID of the parent location'),
        new OA\Property(property: 'order', type: 'integer', description: 'Display order'),
        new OA\Property(property: 'image_url', type: 'string', format: 'uri', description: 'URL of the map image'),
    ]
)]
class SummitLocationMapSchema {}
