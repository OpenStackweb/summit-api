<?php
namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;

/**
 * Summit Bookable Venue Room Attribute Type Schemas
 * Schemas for SummitBookableVenueRoomAttributeType entities
 */

#[OA\Schema(
    schema: 'SummitBookableVenueRoomAttributeType',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1, description: 'Attribute type ID'),
        new OA\Property(property: 'created', type: 'integer', example: 1630500518, description: 'Creation timestamp (Unix)'),
        new OA\Property(property: 'last_edited', type: 'integer', example: 1630500518, description: 'Last edit timestamp (Unix)'),
        new OA\Property(property: 'type', type: 'string', example: 'Room Size', description: 'The attribute type name'),
        new OA\Property(property: 'summit_id', type: 'integer', example: 1, description: 'Associated summit ID'),
        new OA\Property(
            property: 'values',
            type: 'array',
            items: new OA\Items(type: 'integer', example: 1),
            example: [1, 2, 3],
            description: 'List of attribute value IDs'
        ),
    ]
)]
class SummitBookableVenueRoomAttributeTypeSchema {}

#[OA\Schema(
    schema: 'SummitBookableVenueRoomAttributeTypeExpanded',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1, description: 'Attribute type ID'),
        new OA\Property(property: 'created', type: 'integer', example: 1630500518, description: 'Creation timestamp (Unix)'),
        new OA\Property(property: 'last_edited', type: 'integer', example: 1630500518, description: 'Last edit timestamp (Unix)'),
        new OA\Property(property: 'type', type: 'string', example: 'Room Size', description: 'The attribute type name'),
        new OA\Property(property: 'summit_id', type: 'integer', example: 1, description: 'Associated summit ID'),
        new OA\Property(
            property: 'values',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/SummitBookableVenueRoomAttributeValue'),
            description: 'Expanded list of attribute values'
        ),
    ]
)]
class SummitBookableVenueRoomAttributeTypeExpandedSchema {}
