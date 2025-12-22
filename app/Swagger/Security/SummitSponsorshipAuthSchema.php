<?php

namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;
use App\Security\SummitScopes;

#[
    OA\SecurityScheme(
        type: 'oauth2',
        securityScheme: 'summit_sponsorship_oauth2',
        flows: [
            new OA\Flow(
                authorizationUrl: L5_SWAGGER_CONST_AUTH_URL,
                tokenUrl: L5_SWAGGER_CONST_TOKEN_URL,
                flow: 'authorizationCode',
                scopes: [
                    SummitScopes::ReadSummitData => 'Read Summit Data',
                    SummitScopes::ReadAllSummitData => 'Read All Summit Data',
                    SummitScopes::WriteSummitData => 'Write Summit Data',
                ],
            ),
        ],
    )
]
class SummitSponsorshipAuthSchema {}

