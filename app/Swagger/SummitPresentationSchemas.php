<?php

namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'PresentationCategoryGroup',
    type: 'object',
    required: ['id', 'created', 'last_edited', 'name', 'class_name', 'summit_id', 'max_attendee_votes'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'created', type: 'integer', example: 1634567890),
        new OA\Property(property: 'last_edited', type: 'integer', example: 1634567890),
        new OA\Property(property: 'name', type: 'string', example: 'Track Group Name'),
        new OA\Property(property: 'color', type: 'string', example: '#FF5733'),
        new OA\Property(property: 'description', type: 'string', example: 'Group description'),
        new OA\Property(property: 'class_name', type: 'string', example: 'PresentationCategoryGroup'),
        new OA\Property(property: 'summit_id', type: 'integer', example: 1),
        new OA\Property(property: 'begin_attendee_voting_period_date', type: 'integer', example: 1634567890),
        new OA\Property(property: 'end_attendee_voting_period_date', type: 'integer', example: 1634567890),
        new OA\Property(property: 'max_attendee_votes', type: 'integer', example: 3),
        new OA\Property(
            property: 'tracks',
            type: 'array',
            items: new OA\Items(
                anyOf: [
                    new OA\Schema(type: 'integer'),
                    new OA\Schema(type: 'PresentationCategory')
                ]
            )
        ),
    ]
)]
class PresentationCategoryGroup {}

#[OA\Schema(
    schema: 'PaginatedPresentationCategoryGroupsResponse',
    allOf: [
        new OA\Schema(ref: '#/components/schemas/PaginateDataSchemaResponse'),
        new OA\Schema(
            type: 'object',
            properties: [
                new OA\Property(
                    property: 'data',
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/PresentationCategoryGroup')
                )
            ]
        )
    ]
)]
class PaginatedPresentationCategoryGroupsResponse {}

#[OA\Schema(
    schema: 'PresentationCategoryGroupRequest',
    type: 'object',
    required: ['name', 'class_name'],
    properties: [
        new OA\Property(property: 'name', type: 'string', example: 'Track Group Name'),
        new OA\Property(property: 'class_name', type: 'string', example: 'PresentationCategoryGroup'),
        new OA\Property(property: 'description', type: 'string', example: 'Group description'),
        new OA\Property(property: 'color', type: 'string', example: '#FF5733'),
        new OA\Property(property: 'max_attendee_votes', type: 'integer', example: 3),
        new OA\Property(property: 'begin_attendee_voting_period_date', type: 'integer', example: 1634567890),
        new OA\Property(property: 'end_attendee_voting_period_date', type: 'integer', example: 1634567890),
        new OA\Property(property: 'submission_begin_date', type: 'integer', example: 1634567890),
        new OA\Property(property: 'submission_end_date', type: 'integer', example: 1634567890),
        new OA\Property(property: 'max_submission_allowed_per_user', type: 'integer', example: 5),
    ]
)]
class PresentationCategoryGroupRequest {}

//
