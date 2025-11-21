<?php
namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;

/**
 * Summit Bookable Venue Room Attribute Value Schemas
 * Schemas for SummitBookableVenueRoomAttributeValue entities
 */

#[OA\Schema(
    schema: 'SummitBookableVenueRoomAttributeValue',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1, description: 'Attribute value ID'),
        new OA\Property(property: 'created', type: 'integer', example: 1630500518, description: 'Creation timestamp (Unix)'),
        new OA\Property(property: 'last_edited', type: 'integer', example: 1630500518, description: 'Last edit timestamp (Unix)'),
        new OA\Property(property: 'value', type: 'string', example: 'Large', description: 'The attribute value text'),
        new OA\Property(property: 'type_id', type: 'integer', example: 1, description: 'Associated attribute type ID'),
    ]
)]
class SummitBookableVenueRoomAttributeValueSchema {}

#[OA\Schema(
    schema: 'SummitBookableVenueRoomAttributeValueExpanded',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1, description: 'Attribute value ID'),
        new OA\Property(property: 'created', type: 'integer', example: 1630500518, description: 'Creation timestamp (Unix)'),
        new OA\Property(property: 'last_edited', type: 'integer', example: 1630500518, description: 'Last edit timestamp (Unix)'),
        new OA\Property(property: 'value', type: 'string', example: 'Large', description: 'The attribute value text'),
        new OA\Property(
            property: 'type',
            ref: '#/components/schemas/SummitBookableVenueRoomAttributeType',
            description: 'Expanded attribute type object'
        ),
    ]
)]
class SummitBookableVenueRoomAttributeValueExpandedSchema {}
