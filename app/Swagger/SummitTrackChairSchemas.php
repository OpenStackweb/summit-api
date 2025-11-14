<?php

namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'SummitTrackChair',
    type: 'object',
    description: 'Public view of track chair (limited member info without email)',
    required: ['id', 'created', 'last_edited'],
    properties: [
        new OA\Property(property: 'id', type: 'integer'),
        new OA\Property(property: 'created', type: 'integer', description: 'Unix timestamp'),
        new OA\Property(property: 'last_edited', type: 'integer', description: 'Unix timestamp'),
        new OA\Property(
            property: 'categories', 
            type: 'array', 
            items: new OA\Items(type: ['integer', 'PresentationCategory']), 
            description: 'Array of category IDs by default. Use expand=categories to get full objects. Use relations=categories to include'
        ),
    ],
    anyOf: [
        new OA\Property(property: 'summit_id', type: 'integer', example: 10, description: 'Summit ID when not expanded'),
        new OA\Property(property: 'summit', type: 'Summit', description: 'Full Summit object when expanded (expand=summit)'),
        new OA\Property(property: 'member_id', type: 'integer', example: 123, description: 'Member ID when not expanded'),
        new OA\Property(property: 'member', type: 'Member', description: 'Full Member object (without email) when expanded (expand=member)'),
    ]
)]
class SummitTrackChairSchema {}

#[OA\Schema(
    schema: 'AdminSummitTrackChair',
    type: 'object',
    description: 'Admin view of track chair (includes member email)',
    required: ['id', 'created', 'last_edited'],
    properties: [
        new OA\Property(property: 'id', type: 'integer'),
        new OA\Property(property: 'created', type: 'integer', description: 'Unix timestamp'),
        new OA\Property(property: 'last_edited', type: 'integer', description: 'Unix timestamp'),
        new OA\Property(
            property: 'categories', 
            type: 'array', 
            items: new OA\Items(type: ['integer', 'PresentationCategory']), 
            description: 'Array of category IDs by default. Use expand=categories to get full objects. Use relations=categories to include'
        ),
    ],
    anyOf: [
        new OA\Property(property: 'summit_id', type: 'integer', example: 10, description: 'Summit ID when not expanded'),
        new OA\Property(property: 'summit', type: 'Summit', description: 'Full Summit object when expanded (expand=summit)'),
        new OA\Property(property: 'member_id', type: 'integer', example: 123, description: 'Member ID when not expanded'),
        new OA\Property(property: 'member', type: 'AdminMember', description: 'Full Member object (WITH email) when expanded (expand=member)'),
    ]
)]
class AdminSummitTrackChairSchema {}

#[OA\Schema(
    schema: 'PaginatedTrackChairsResponse',
    allOf: [
        new OA\Schema(ref: '#/components/schemas/PaginateDataSchemaResponse'),
        new OA\Schema(
            type: 'object',
            properties: [
                new OA\Property(
                    property: 'data',
                    type: 'array',
                    items: new OA\Items(
                        oneOf: [
                            new OA\Schema(ref: '#/components/schemas/SummitTrackChair'),
                            new OA\Schema(ref: '#/components/schemas/AdminSummitTrackChair')
                        ]
                    )
                )
            ]
        )
    ]
)]
class PaginatedTrackChairsResponseSchema {}

#[OA\Schema(
    schema: 'TrackChairAddRequest',
    type: 'object',
    required: ['member_id', 'categories'],
    properties: [
        new OA\Property(
            property: 'member_id',
            type: 'integer',
            description: 'Member ID to assign as track chair',
            example: 123
        ),
        new OA\Property(
            property: 'categories',
            type: 'array',
            items: new OA\Items(type: 'integer', example: 1),
            description: 'Array of track/category IDs this chair will manage'
        ),
    ]
)]
class TrackChairAddRequestSchema {}

#[OA\Schema(
    schema: 'TrackChairUpdateRequest',
    type: 'object',
    required: ['categories'],
    properties: [
        new OA\Property(
            property: 'categories',
            type: 'array',
            items: new OA\Items(type: 'integer', example: 1),
            description: 'Array of track/category IDs this chair will manage'
        ),
    ]
)]
class TrackChairUpdateRequestSchema {}
