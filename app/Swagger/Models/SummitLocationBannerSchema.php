<?php

namespace App\Swagger\Models;

use OpenApi\Attributes as OA;

/**
 * Schema for SummitLocationBanner model
 */
#[OA\Schema(
    schema: 'SummitLocationBanner',
    type: 'object',
    description: 'Summit location banner',
    properties: [
        new OA\Property(property: 'id', type: 'integer', description: 'Unique identifier'),
        new OA\Property(property: 'created', type: 'integer', format: 'int64', description: 'Creation timestamp (epoch)'),
        new OA\Property(property: 'last_edited', type: 'integer', format: 'int64', description: 'Last modification timestamp (epoch)'),
        new OA\Property(property: 'title', type: 'string', description: 'Banner title'),
        new OA\Property(property: 'content', type: 'string', description: 'Banner content/message'),
        new OA\Property(property: 'type', type: 'string', description: 'Banner type (Primary, Secondary)'),
        new OA\Property(property: 'enabled', type: 'boolean', description: 'Whether the banner is enabled'),
        new OA\Property(property: 'location_id', type: 'integer', description: 'ID of the parent location'),
        new OA\Property(property: 'class_name', type: 'string', description: 'Class type'),
    ]
)]
class SummitLocationBannerSchema {}
