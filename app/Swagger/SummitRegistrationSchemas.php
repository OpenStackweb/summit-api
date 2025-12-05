<?php

namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;

//

// Summit Badge Feature Types

#[OA\Schema(
    schema: 'PaginatedSummitBadgeFeatureTypesResponse',
    allOf: [
        new OA\Schema(ref: '#/components/schemas/PaginateDataSchemaResponse'),
        new OA\Schema(
            type: 'object',
            properties: [
                new OA\Property(
                    property: 'data',
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/SummitBadgeFeatureType')
                )
            ]
        )
    ]
)]
class PaginatedSummitBadgeFeatureTypesResponseSchema {}

#[OA\Schema(
    schema: 'SummitBadgeFeatureTypeCreateRequest',
    type: 'object',
    required: ['name'],
    properties: [
        new OA\Property(property: 'name', type: 'string', example: 'Speaker Ribbon'),
        new OA\Property(property: 'description', type: 'string', example: 'Special ribbon for speakers'),
        new OA\Property(property: 'template_content', type: 'string', example: '<div>{{name}}</div>'),
    ]
)]
class SummitBadgeFeatureTypeCreateRequestSchema {}

#[OA\Schema(
    schema: 'SummitBadgeFeatureTypeUpdateRequest',
    type: 'object',
    properties: [
        new OA\Property(property: 'name', type: 'string', example: 'VIP Ribbon'),
        new OA\Property(property: 'description', type: 'string', example: 'VIP attendee designation'),
        new OA\Property(property: 'template_content', type: 'string', example: '<div class="vip">{{name}}</div>'),
    ]
)]
class SummitBadgeFeatureTypeUpdateRequestSchema {}
