<?php

namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'PaginatedPresentationCategoryGroupsResponse',
    type: 'object',
    description: 'Paginated response containing presentation category groups (track groups)',
    allOf: [
        new OA\Schema(ref: '#/components/schemas/PaginateDataSchemaResponse'),
        new OA\Schema(
            type: 'object',
            properties: [
                new OA\Property(
                    property: 'data',
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/PresentationCategoryGroup'),
                    description: 'Array of presentation category groups'
                )
            ]
        )
    ]
)]
class PaginatedPresentationCategoryGroupsResponseSchema {}

#[OA\Schema(
    schema: 'PresentationCategoryGroupRequest',
    type: 'object',
    description: 'Request body for creating or updating a presentation category group (track group)',
    required: ['name', 'class_name'],
    properties: [
        new OA\Property(
            property: 'class_name',
            type: 'string',
            description: 'The class name of the category group',
            enum: ['PresentationCategoryGroup', 'PrivatePresentationCategoryGroup'],
            example: 'PresentationCategoryGroup'
        ),
        new OA\Property(
            property: 'name',
            type: 'string',
            description: 'The name of the track group',
            example: 'Track Group Name'
        ),
        new OA\Property(
            property: 'description',
            type: 'string',
            description: 'The description of the track group',
            example: 'Track group description'
        ),
        new OA\Property(
            property: 'color',
            type: 'string',
            description: 'The color of the track group in hex format',
            example: '#FF5733'
        ),
        new OA\Property(
            property: 'max_attendee_votes',
            type: 'integer',
            description: 'Maximum number of votes allowed per attendee',
            minimum: 0,
            example: 3
        ),
        new OA\Property(
            property: 'begin_attendee_voting_period_date',
            type: 'integer',
            description: 'Start date of attendee voting period (Unix timestamp)',
            format: 'int64',
            example: 1634567890
        ),
        new OA\Property(
            property: 'end_attendee_voting_period_date',
            type: 'integer',
            description: 'End date of attendee voting period (Unix timestamp). Must be after begin_attendee_voting_period_date',
            format: 'int64',
            example: 1634654290
        ),
        new OA\Property(
            property: 'submission_begin_date',
            type: 'integer',
            description: 'Start date of submission period (Unix timestamp). Only for PrivatePresentationCategoryGroup',
            format: 'int64',
            example: 1634567890
        ),
        new OA\Property(
            property: 'submission_end_date',
            type: 'integer',
            description: 'End date of submission period (Unix timestamp). Only for PrivatePresentationCategoryGroup. Must be after submission_begin_date',
            format: 'int64',
            example: 1634654290
        ),
        new OA\Property(
            property: 'max_submission_allowed_per_user',
            type: 'integer',
            description: 'Maximum number of submissions allowed per user. Only for PrivatePresentationCategoryGroup',
            minimum: 1,
            example: 5
        ),
    ]
)]
class PresentationCategoryGroupRequestSchema {}

