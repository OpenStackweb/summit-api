<?php

namespace App\Swagger\schemas;

use App\Security\SummitScopes;
use OpenApi\Attributes as OA;



#[
    OA\SecurityScheme(
        type: 'oauth2',
        securityScheme: 'summit_submitters_oauth2',
        flows: [
            new OA\Flow(
                authorizationUrl: L5_SWAGGER_CONST_AUTH_URL,
                tokenUrl: L5_SWAGGER_CONST_TOKEN_URL,
                flow: 'authorizationCode',
                scopes: [
                    SummitScopes::ReadSummitData => 'Read summit data',
                    SummitScopes::ReadAllSummitData => 'Read all summit data',
                    SummitScopes::WriteSummitData => 'Write summit data',
                    SummitScopes::WriteSpeakersData => 'Write speakers data',
                ],
            ),
        ],
    )
]
class SummitSubmittersOAuthSchema {}

