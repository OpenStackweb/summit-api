<?php

namespace App\Swagger\schemas;

use App\Security\MemberScopes;
use OpenApi\Attributes as OA;

#[
    OA\SecurityScheme(
        type: 'oauth2',
        securityScheme: 'members_oauth2',
        flows: [
            new OA\Flow(
                authorizationUrl: L5_SWAGGER_CONST_AUTH_URL,
                tokenUrl: L5_SWAGGER_CONST_TOKEN_URL,
                flow: 'authorizationCode',
                scopes: [
                    MemberScopes::ReadMemberData => 'Read Member Data',
                    MemberScopes::ReadMyMemberData => 'Read My Member Data',
                    MemberScopes::WriteMemberData => 'Write Member Data',
                    MemberScopes::WriteMyMemberData => 'Write My Member Data',
                ],
            ),
        ],
    )
]
class MembersAuthSchema {}
