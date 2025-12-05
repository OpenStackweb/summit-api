<?php

namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'MembersList',
    allOf: [
        new OA\Schema(ref: '#/components/schemas/PaginateDataSchemaResponse'),
        new OA\Schema(
            type: 'object',
            properties: [
                new OA\Property(
                    property: 'data',
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/Member'),
                    description: 'Array of members'
                )
            ]
        )
    ],
    description: 'Paginated list of members'
)]
class MembersListSchema {}

#[OA\Schema(
    schema: 'MemberFavoriteEventsList',
    type: 'object',
    properties: [
        new OA\Property(property: 'data', type: 'array', items: new OA\Items(type: 'object'), description: 'Array of favorite events'),
        new OA\Property(property: 'page', type: 'integer', example: 1),
        new OA\Property(property: 'per_page', type: 'integer', example: 10),
        new OA\Property(property: 'total', type: 'integer', example: 25),
    ],
    description: 'Paginated list of member favorite events'
)]
class MemberFavoriteEventsListSchema {}

#[OA\Schema(
    schema: 'MemberScheduleEventsList',
    type: 'object',
    properties: [
        new OA\Property(property: 'data', type: 'array', items: new OA\Items(type: 'object'), description: 'Array of schedule events'),
        new OA\Property(property: 'page', type: 'integer', example: 1),
        new OA\Property(property: 'per_page', type: 'integer', example: 10),
        new OA\Property(property: 'total', type: 'integer', example: 25),
    ],
    description: 'Paginated list of member schedule events'
)]
class MemberScheduleEventsListSchema {}
