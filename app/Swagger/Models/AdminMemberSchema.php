<?php

namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;



#[OA\Schema(
    schema: 'AdminMember',
    allOf: [
        new OA\Schema(ref: '#/components/schemas/Member'),
        new OA\Schema(
            type: 'object',
            properties: [
                new OA\Property(property: 'email', type: 'string', format: 'email'),
                new OA\Property(property: 'second_email', type: 'string', format: 'email'),
                new OA\Property(property: 'third_email', type: 'string', format: 'email'),
                new OA\Property(property: 'user_external_id', type: 'integer'),
                new OA\Property(property: 'rsvp', type: 'array', items: new OA\Items(type: 'integer'), description: 'Array of RSVP IDs, full objects available when expand=rsvp'),
                new OA\Property(property: 'rsvp_invitations', type: 'array', items: new OA\Items(type: 'integer'), description: 'Array of RSVP Invitation IDs, full objects available when expand=rsvp_invitations'),
            ]
        )
    ]
)]
class AdminMemberSchema {}
