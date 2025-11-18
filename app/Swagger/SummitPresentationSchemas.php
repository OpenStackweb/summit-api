<?php

namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'PresentationActionType',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'created', type: 'integer', example: 1630500518),
        new OA\Property(property: 'last_edited', type: 'integer', example: 1630500518),
        new OA\Property(property: 'label', type: 'string', example: 'Review'),
        new OA\Property(property: 'summit_id', type: 'integer', example: 42),
        new OA\Property(property: 'order', type: 'integer', example: 1, description: 'Order within a selection plan. Only present when filtering by selection_plan_id',),
    ]
)]
class PresentationActionTypeSchema {}

#[OA\Schema(
    schema: 'PaginatedPresentationActionTypesResponse',
    allOf: [
        new OA\Schema(ref: '#/components/schemas/PaginateDataSchemaResponse'),
        new OA\Schema(
            type: 'object',
            properties: [
                new OA\Property(
                    property: 'data',
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/PresentationActionType')
                )
            ]
        )
    ]
)]
class PaginatedPresentationActionTypesResponseSchema {}

#[OA\Schema(
    schema: 'PresentationActionTypeCreateRequest',
    type: 'object',
    required: ['label'],
    properties: [
        new OA\Property(property: 'label', type: 'string', example: 'Review', maxLength: 255),
    ]
)]
class PresentationActionTypeCreateRequestSchema {}

#[OA\Schema(
    schema: 'PresentationActionTypeUpdateRequest',
    type: 'object',
    properties: [
        new OA\Property(property: 'label', type: 'string', example: 'Review', maxLength: 255),
    ]
)]
class PresentationActionTypeUpdateRequestSchema {}
