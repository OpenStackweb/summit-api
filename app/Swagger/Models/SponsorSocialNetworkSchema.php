<?php

namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'SponsorSocialNetwork',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'sponsor_id', type: 'integer', example: 1),
        new OA\Property(property: 'link', type: 'string', example: 'https://twitter.com/example'),
        new OA\Property(property: 'enabled', type: 'boolean', example: true),
        new OA\Property(property: 'icon_css_class', type: 'string', example: 'fab fa-twitter'),
    ]
)]
class SponsorSocialNetworkSchema {}
