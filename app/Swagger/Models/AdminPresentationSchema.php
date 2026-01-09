<?php

namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'AdminPresentation',
    type: 'object',
    description: 'Represents a presentation with admin-specific fields including statistics and selection data',
    allOf: [
        new OA\Schema(ref: '#/components/schemas/Presentation'),
        new OA\Schema(
            type: 'object',
            properties: [
                // AdminPresentation-specific fields
                new OA\Property(property: 'rank', type: 'integer', nullable: true, example: 5, description: 'Presentation rank in selection'),
                new OA\Property(property: 'selection_status', type: 'string', nullable: true, example: 'selected', description: 'Selection status (selected, unselected, lightning-accepted, etc.)'),
                new OA\Property(property: 'views_count', type: 'integer', example: 150, description: 'Number of views by track chairs'),
                new OA\Property(property: 'comments_count', type: 'integer', example: 8, description: 'Number of comments'),
                new OA\Property(property: 'popularity_score', type: 'number', format: 'float', example: 4.5, description: 'Calculated popularity score'),
                new OA\Property(property: 'votes_count', type: 'integer', example: 42, description: 'Number of attendee votes'),
                new OA\Property(property: 'votes_average', type: 'number', format: 'float', example: 3.8, description: 'Average vote rating'),
                new OA\Property(property: 'votes_total_points', type: 'integer', example: 160, description: 'Total vote points'),
                new OA\Property(property: 'track_chair_avg_score', type: 'number', format: 'float', example: 4.2, description: 'Average track chair score'),
                new OA\Property(property: 'remaining_selections', type: 'integer', example: 3, description: 'Remaining selections available'),
                new OA\Property(property: 'passers_count', type: 'integer', example: 2, description: 'Number of track chairs who passed'),
                new OA\Property(property: 'likers_count', type: 'integer', example: 5, description: 'Number of track chairs who marked as maybe'),
                new OA\Property(property: 'selectors_count', type: 'integer', example: 8, description: 'Number of track chairs who selected'),
                new OA\Property(
                    property: 'track_chair_scores_avg',
                    type: 'array',
                    description: 'Average scores per ranking type',
                    items: new OA\Items(type: 'string'),
                    example: ['Content: 4.5', 'Quality: 4.0']
                ),

                // Streaming/occupancy fields
                new OA\Property(property: 'occupancy', type: 'string', nullable: true, example: 'FULL', description: 'Room occupancy status'),
                new OA\Property(property: 'streaming_url', type: 'string', format: 'uri', nullable: true, example: 'https://stream.example.com/live/123'),
                new OA\Property(property: 'streaming_type', type: 'string', nullable: true, example: 'VOD', description: 'Type of streaming (VOD, LIVE, etc.)'),
                new OA\Property(property: 'etherpad_link', type: 'string', format: 'uri', nullable: true, example: 'https://etherpad.example.com/p/session123'),
                new OA\Property(property: 'overflow_streaming_url', type: 'string', format: 'uri', nullable: true, example: 'https://overflow.example.com/live/123'),
                new OA\Property(property: 'overflow_stream_is_secure', type: 'boolean', example: false),
                new OA\Property(property: 'overflow_stream_key', type: 'string', nullable: true, example: 'abc123key'),
            ]
        ),
    ],
)]
class AdminPresentationSchema {}
