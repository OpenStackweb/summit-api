<?php

namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'TrackChairPresentation',
    type: 'object',
    description: 'Represents a presentation with track chair specific fields and relations',
    allOf: [
        new OA\Schema(ref: '#/components/schemas/AdminPresentation'),
        new OA\Schema(
            type: 'object',
            properties: [
                // TrackChairPresentation-specific fields
                new OA\Property(property: 'is_group_selected', type: 'boolean', example: false, description: 'Whether the presentation has been group selected'),
                new OA\Property(property: 'pending_category_change_requests_count', type: 'integer', example: 0, description: 'Number of pending category change requests'),
                new OA\Property(property: 'viewed', type: 'boolean', example: true, description: 'Whether current track chair has viewed this presentation'),
                new OA\Property(property: 'selected', type: 'boolean', example: false, description: 'Whether current track chair has selected this presentation'),
                new OA\Property(property: 'maybe', type: 'boolean', example: false, description: 'Whether current track chair marked this as maybe'),
                new OA\Property(property: 'pass', type: 'boolean', example: false, description: 'Whether current track chair passed on this presentation'),
                new OA\Property(property: 'remaining_selections', type: 'integer', example: 5, description: 'Remaining selections available for the current member'),

                // TrackChairPresentation-specific relations (arrays of IDs by default, expandable)
                new OA\Property(
                    property: 'selectors',
                    type: 'array',
                    description: 'Array of Member IDs who selected this presentation, use expand=selectors for full details',
                    items: new OA\Items(oneOf: [
                        new OA\Schema(type: 'integer'),
                        new OA\Schema(ref: '#/components/schemas/Member'),
                    ]),
                    example: [1, 2, 3]
                ),
                new OA\Property(
                    property: 'likers',
                    type: 'array',
                    description: 'Array of Member IDs who liked (maybe) this presentation, use expand=likers for full details',
                    items: new OA\Items(oneOf: [
                        new OA\Schema(type: 'integer'),
                        new OA\Schema(ref: '#/components/schemas/Member'),
                    ]),
                    example: [4, 5]
                ),
                new OA\Property(
                    property: 'passers',
                    type: 'array',
                    description: 'Array of Member IDs who passed on this presentation, use expand=passers for full details',
                    items: new OA\Items(oneOf: [
                        new OA\Schema(type: 'integer'),
                        new OA\Schema(ref: '#/components/schemas/Member'),
                    ]),
                    example: [6]
                ),
                new OA\Property(
                    property: 'viewers',
                    type: 'array',
                    description: 'Array of Member IDs who viewed this presentation, use expand=viewers for full details',
                    items: new OA\Items(oneOf: [
                        new OA\Schema(type: 'integer'),
                        new OA\Schema(ref: '#/components/schemas/Member'),
                    ]),
                    example: [1, 2, 3, 4, 5, 6]
                ),
                new OA\Property(
                    property: 'comments',
                    type: 'array',
                    description: 'Array of SummitPresentationComment IDs (all comments), use expand=comments for full details',
                    items: new OA\Items(oneOf: [
                        new OA\Schema(type: 'integer'),
                        new OA\Schema(ref: '#/components/schemas/SummitPresentationComment'),
                    ]),
                    example: [1, 2]
                ),
                new OA\Property(
                    property: 'category_changes_requests',
                    type: 'array',
                    description: 'Array of SummitCategoryChange IDs, use expand=category_changes_requests for full details',
                    items: new OA\Items(oneOf: [
                        new OA\Schema(type: 'integer'),
                        new OA\Schema(ref: '#/components/schemas/SummitCategoryChange'),
                    ]),
                    example: [1]
                ),
                new OA\Property(
                    property: 'track_chair_scores',
                    type: 'array',
                    description: 'Array of TrackChairScore IDs for current track chair, use expand=track_chair_scores for full details',
                    items: new OA\Items(type: 'integer'),
                    example: [1, 2]
                ),
            ]
        ),
    ],
)]
class TrackChairPresentationSchema {}
