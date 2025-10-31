<?php
namespace App\Swagger\schemas;
use OpenApi\Attributes as OA;


#[OA\Schema(
    schema: 'SummitBookableVenueRoomAttributeType',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'created', type: 'integer', example: 1630500518),
        new OA\Property(property: 'last_edited', type: 'integer', example: 1630500518),
        new OA\Property(property: 'type', type: 'string', example: 'Room Size'),
        new OA\Property(property: 'summit_id', type: 'integer', example: 1),
        new OA\Property(
            property: 'values',
            type: 'array',
            items: new OA\Items(type: 'integer', example: 1),
            example: [1, 2, 3]
        ),
    ]
)]
class SummitBookableVenueRoomAttributeTypeSchema {}

#[OA\Schema(
    schema: 'SummitBookableVenueRoomAttributeTypeExpanded',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'created', type: 'integer', example: 1630500518),
        new OA\Property(property: 'last_edited', type: 'integer', example: 1630500518),
        new OA\Property(property: 'type', type: 'string', example: 'Room Size'),
        new OA\Property(property: 'summit_id', type: 'integer', example: 1),
        new OA\Property(
            property: 'values',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/SummitBookableVenueRoomAttributeValue')
        ),
    ]
)]
class SummitBookableVenueRoomAttributeTypeExpandedSchema {}

#[OA\Schema(
    schema: 'SummitBookableVenueRoomAttributeValue',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'created', type: 'integer', example: 1630500518),
        new OA\Property(property: 'last_edited', type: 'integer', example: 1630500518),
        new OA\Property(property: 'value', type: 'string', example: 'Large'),
        new OA\Property(property: 'type_id', type: 'integer', example: 1),
    ]
)]
class SummitBookableVenueRoomAttributeValueSchema {}

#[OA\Schema(
    schema: 'SummitBookableVenueRoomAttributeValueExpanded',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'created', type: 'integer', example: 1630500518),
        new OA\Property(property: 'last_edited', type: 'integer', example: 1630500518),
        new OA\Property(property: 'value', type: 'string', example: 'Large'),
        new OA\Property(property: 'type', ref: '#/components/schemas/SummitBookableVenueRoomAttributeType'),
    ]
)]
class SummitBookableVenueRoomAttributeValueExpandedSchema {}


#[OA\Schema(
    schema: 'BookableRoomAttributeTypeCreateRequest',
    type: 'object',
    required: ['type'],
    properties: [
        new OA\Property(property: 'type', type: 'string', example: 'Room Size', description: 'The attribute type name'),
    ]
)]
class BookableRoomAttributeTypeCreateRequestSchema {}

#[OA\Schema(
    schema: 'BookableRoomAttributeValueCreateRequest',
    type: 'object',
    required: ['value'],
    properties: [
        new OA\Property(property: 'value', type: 'string', example: 'Large', description: 'The attribute value'),
    ]
)]
class BookableRoomAttributeValueCreateRequestSchema {}
