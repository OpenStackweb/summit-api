<?php

namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;


#[OA\Schema(
    schema: 'Group',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1, description: 'Unique identifier'),
        new OA\Property(property: 'created', type: 'integer', example: 1630500518, description: 'Creation timestamp (Unix epoch)'),
        new OA\Property(property: 'last_edited', type: 'integer', example: 1630500518, description: 'Last modification timestamp (Unix epoch)'),
        new OA\Property(property: 'title', type: 'string', example: 'Administrators', description: 'Group title'),
        new OA\Property(property: 'description', type: 'string', example: 'System administrators group', description: 'Group description', nullable: true),
        new OA\Property(property: 'code', type: 'string', example: 'administrators', description: 'Unique group code'),
        new OA\Property(
            property: 'members',
            type: 'array',
            description: 'List of Member objects, only present when requested via ?expand=members',
            items: new OA\Items(
                ref: '#/components/schemas/Member'
            )
        ),
    ]
)]
class GroupSchema
{
}
