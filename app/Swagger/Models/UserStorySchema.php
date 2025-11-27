<?php

namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;



#[OA\Schema(
    schema: 'UserStory',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'created', type: 'integer', example: 1633024800, description: 'Unix timestamp when created'),
        new OA\Property(property: 'last_edited', type: 'integer', example: 1633111200, description: 'Unix timestamp when last updated'),
        new OA\Property(property: 'name', type: 'string', example: 'Large Scale Cloud Infrastructure'),
        new OA\Property(property: 'description', type: 'string', example: 'Full description of how this organization uses OpenStack...'),
        new OA\Property(property: 'short_description', type: 'string', example: 'Brief overview of the use case'),
        new OA\Property(property: 'link', type: 'string', nullable: true, example: 'https://example.com/case-study'),
        new OA\Property(property: 'active', type: 'boolean', example: true),
        new OA\Property(property: 'is_million_core_club', type: 'boolean', example: false, description: 'Whether this is a million core club member'),
        new OA\Property(property: 'organization_id', type: 'integer', example: 14),
        new OA\Property(property: 'industry_id', type: 'integer', example: 14),
        new OA\Property(property: 'location_id', type: 'integer', example: 14),
        new OA\Property(property: 'image_id', type: 'integer', example: 14),
        new OA\Property(property: 'organization', ref: '#/components/schemas/Organization', description: 'Organization object, only available if expanded via ?expand=organization'),
        new OA\Property(property: 'industry', ref: '#/components/schemas/UserStoriesIndustry', description: 'UserStoriesIndustry object, only available if expanded via ?expand=industry'),
        new OA\Property(property: 'location', ref: '#/components/schemas/Continent', description: 'Continent object, only available if expanded via ?expand=location'),
        new OA\Property(property: 'image', ref: '#/components/schemas/File', description: 'File object, only available if expanded via ?expand=image'),
        new OA\Property(
            property: 'tags',
            type: 'array',
            description: 'Array of tag IDs, available only if included in ?relations=tags, use expand=tags for full objects instead of IDs',
            items: new OA\Items(type: 'integer'),
            example: [1, 2, 3]
        ),
    ]
)]
class UserStorySchema {}
