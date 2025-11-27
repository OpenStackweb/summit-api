<?php

namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;


#[OA\Schema(
    schema: 'MarketPlaceReview',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'created', type: 'integer', example: 1),
        new OA\Property(property: 'last_edited', type: 'integer', example: 1),
        new OA\Property(property: 'title', type: 'string', example: 'Great service'),
        new OA\Property(property: 'comment', type: 'string', example: 'Really enjoyed the experience'),
        new OA\Property(property: 'rating', type: 'integer', example: 5),

    ])
]
class MarketPlaceReviewSchema
{
}
