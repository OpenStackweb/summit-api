<?php

namespace App\Swagger\Models;

use OpenApi\Attributes as OA;

/**
 * Schema for SummitBookableVenueRoomAttributeValue model
 */
#[OA\Schema(
    schema: 'SummitBookableVenueRoomAttributeValue',
    type: 'object',
    description: 'Bookable venue room attribute value',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1, description: 'Attribute value ID'),
        new OA\Property(property: 'created', type: 'integer', example: 1630500518, description: 'Creation timestamp (Unix)'),
        new OA\Property(property: 'last_edited', type: 'integer', example: 1630500518, description: 'Last edit timestamp (Unix)'),
        new OA\Property(property: 'value', type: 'string', example: 'Large', description: 'The attribute value text'),
        new OA\Property(property: 'type_id', type: 'integer', example: 1, description: 'Associated attribute type ID'),
    ]
)]
class SummitBookableVenueRoomAttributeValueSchema {}
