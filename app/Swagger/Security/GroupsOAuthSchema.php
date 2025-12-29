<?php
namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;
use App\Security\GroupsScopes;
use App\Security\SummitScopes;

#[OA\SecurityScheme(
    type: 'oauth2',
    securityScheme: 'groups_oauth2',
    flows: [
        new OA\Flow(
            authorizationUrl: L5_SWAGGER_CONST_AUTH_URL,
            tokenUrl: L5_SWAGGER_CONST_TOKEN_URL,
            flow: 'authorizationCode',
            scopes: [
                SummitScopes::ReadAllSummitData => 'Read All Summit Data',
                SummitScopes::ReadSummitData => 'Read Summit Data',
                GroupsScopes::ReadData => 'Read Groups Data',
            ],
        ),
    ],
)
]
class GroupsOAuthSchema
{
}
