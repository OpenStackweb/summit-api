<?php

namespace App\Swagger\schemas;

use App\Security\SummitScopes;
use OpenApi\Attributes as OA;

#[OA\SecurityScheme(
        type: 'oauth2',
        securityScheme: 'summit_sponsorship_oauth2',
        flows: [
            new OA\Flow(
                authorizationUrl: L5_SWAGGER_CONST_AUTH_URL,
                tokenUrl: L5_SWAGGER_CONST_TOKEN_URL,
                flow: 'authorizationCode',
                scopes: [
                    SummitScopes::ReadSummitData => 'Read Sponsorship Types Data',
                    SummitScopes::ReadAllSummitData => 'Read All Sponsorship Types Data',
                    SummitScopes::WriteSummitData => 'Write Sponsorship Types Data',
                ],
            ),
        ],
    )
]
class SponsorshipTypeAuthSchema {}
