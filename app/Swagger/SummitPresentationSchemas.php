<?php

namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;


#[OA\Schema(
    schema: 'PaginatedPresentationCategoriesResponse',
    type: 'object',
    description: 'Paginated response containing presentation categories',
    allOf: [
        new OA\Schema(ref: '#/components/schemas/PaginateDataSchemaResponse'),
        new OA\Schema(
            type: 'object',
            properties: [
                new OA\Property(
                    property: 'data',
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/PresentationCategory'),
                    description: 'Array of presentation categories'
                )
            ]
        )
    ]
)]
class PaginatedPresentationCategoriesResponseSchema {}


#[OA\Schema(
    schema: 'PaginatedTraksExtraQuestionsResponse',
    type: 'object',
    description: 'Paginated response containing presentation categories',
    allOf: [
        new OA\Schema(ref: '#/components/schemas/PaginateDataSchemaResponse'),
        new OA\Schema(
            type: 'object',
            properties: [
                new OA\Property(
                    property: 'data',
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/TrackQuestionTemplate'),
                    description: 'Array of track question templates'
                )
            ]
        )
    ]
)]
class PaginatedTraksExtraQuestionsResponseSchema {}

#[OA\Schema(
    schema: 'PaginatedPresentationCategoryAllowedTagResponse',
    type: 'object',
    description: 'Paginated response containing presentation categories',
    allOf: [
        new OA\Schema(ref: '#/components/schemas/PaginateDataSchemaResponse'),
        new OA\Schema(
            type: 'object',
            properties: [
                new OA\Property(
                    property: 'data',
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/PresentationCategoryAllowedTag'),
                    description: 'Array of presentation category allowed tags'
                )
            ]
        )
    ]
)]
class PaginatedPresentationCategoryAllowedTagResponseSchema {}
