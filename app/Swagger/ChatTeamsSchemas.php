<?php

namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;

class ChatTeamSchema
{
    #[OA\Schema(
        schema: 'ChatTeam',
        type: 'object',
        properties: [
            new OA\Property(property: 'id', type: 'integer', description: 'Chat Team ID'),
            new OA\Property(property: 'name', type: 'string', description: 'Team/Group name'),
            new OA\Property(property: 'description', type: 'string', description: 'Team/Group description', nullable: true),
            new OA\Property(property: 'created', type: 'integer', format: 'epoch', description: 'Creation timestamp'),
            new OA\Property(property: 'last_edited', type: 'integer', format: 'epoch', description: 'Last edit timestamp'),
            new OA\Property(property: 'is_private', type: 'boolean', description: 'Is team private'),
            new OA\Property(property: 'members_count', type: 'integer', description: 'Number of members'),
        ]
    )]
    public function __construct() {}
}
