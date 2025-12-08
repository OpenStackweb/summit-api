<?php

namespace App\Swagger\schemas;
use App\Security\SponsoredProjectScope;

use OpenApi\Attributes as OA;

#[OA\SecurityScheme(
        type: 'oauth2',
        securityScheme: 'sponsored_projects_oauth2',
        flows: [
            new OA\Flow(
                authorizationUrl: L5_SWAGGER_CONST_AUTH_URL,
                tokenUrl: L5_SWAGGER_CONST_TOKEN_URL,
                flow: 'authorizationCode',
                scopes: [
                    SponsoredProjectScope::Read => 'Read Sponsored Projects',
                    SponsoredProjectScope::Write => 'Write Sponsored Projects',
                ],
            ),
        ],
    )
]
class SponsoredProjectsAuthSchema {}
