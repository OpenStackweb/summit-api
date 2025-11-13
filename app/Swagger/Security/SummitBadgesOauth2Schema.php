<?php

namespace App\Swagger\schemas;

use App\Security\SummitScopes;
use OpenApi\Attributes as OA;

#[OA\SecurityScheme(
        type: 'oauth2',
        securityScheme: 'summit_badges_api_oauth2',
        flows: [
            new OA\Flow(
                authorizationUrl: L5_SWAGGER_CONST_AUTH_URL,
                tokenUrl: L5_SWAGGER_CONST_TOKEN_URL,
                flow: 'authorizationCode',
                scopes: [
                    SummitScopes::ReadAllSummitData => 'Read All Summit Data',
                ],
            ),
        ],
    )
]
class SummitBadgesOAuth2Schema{}
