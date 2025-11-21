<?php
namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;

/**
 * Summit Bookable Rooms Attribute Type Request/Response Schemas
 * Schemas for API requests and paginated responses
 */

#[OA\Schema(
    schema: 'BookableRoomAttributeTypeCreateRequest',
    type: 'object',
    required: ['type'],
    properties: [
        new OA\Property(
            property: 'type',
            type: 'string',
            example: 'Room Size',
            description: 'The attribute type name'
        ),
    ]
)]
class BookableRoomAttributeTypeCreateRequestSchema {}

#[OA\Schema(
    schema: 'BookableRoomAttributeValueCreateRequest',
    type: 'object',
    required: ['value'],
    properties: [
        new OA\Property(
            property: 'value',
            type: 'string',
            example: 'Large',
            description: 'The attribute value'
        ),
    ]
)]
class BookableRoomAttributeValueCreateRequestSchema {}

#[OA\Schema(
    schema: 'PaginatedBookableRoomAttributeTypesResponse',
    type: 'object',
    description: 'Paginated response containing bookable room attribute types',
    allOf: [
        new OA\Schema(ref: '#/components/schemas/PaginateDataSchemaResponse'),
        new OA\Schema(
            type: 'object',
            properties: [
                new OA\Property(
                    property: 'data',
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/SummitBookableVenueRoomAttributeTypeExpanded'),
                    description: 'Array of bookable room attribute types'
                )
            ]
        )
    ]
)]
class PaginatedBookableRoomAttributeTypesResponseSchema {}

#[OA\Schema(
    schema: 'PaginatedBookableRoomAttributeValuesResponse',
    type: 'object',
    description: 'Paginated response containing bookable room attribute values',
    allOf: [
        new OA\Schema(ref: '#/components/schemas/PaginateDataSchemaResponse'),
        new OA\Schema(
            type: 'object',
            properties: [
                new OA\Property(
                    property: 'data',
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/SummitBookableVenueRoomAttributeValueExpanded'),
                    description: 'Array of bookable room attribute values'
                )
            ]
        )
    ]
)]
class PaginatedBookableRoomAttributeValuesResponseSchema {}
