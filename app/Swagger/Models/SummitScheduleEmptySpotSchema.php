<?php

namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;


#[OA\Schema(
    schema: 'SummitScheduleEmptySpot',
    type: 'object',
    properties: [
        new OA\Property(property: 'location_id', type: 'integer', description: 'Location ID'),
        new OA\Property(property: 'start_date', type: 'integer', description: 'Start date (Unix timestamp)'),
        new OA\Property(property: 'end_date', type: 'integer', description: 'End date (Unix timestamp)'),
        new OA\Property(property: 'gap', type: 'integer', description: 'Gap duration in minutes'),
    ])
]
class SummitScheduleEmptySpotSchema
{
}
