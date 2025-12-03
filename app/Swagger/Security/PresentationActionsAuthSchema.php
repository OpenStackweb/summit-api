<?php

namespace App\Swagger\schemas;

use App\Security\SummitScopes;
use OpenApi\Attributes as OA;

#[OA\SecurityScheme(
    type: 'oauth2',
    securityScheme: 'presentation_actions_oauth2',
    flows: [
        new OA\Flow(
            authorizationUrl: L5_SWAGGER_CONST_AUTH_URL,
            tokenUrl: L5_SWAGGER_CONST_TOKEN_URL,
            flow: 'authorizationCode',
            scopes: [
                SummitScopes::WriteSummitData => 'Write Summit Data',
                SummitScopes::WriteEventData => 'Write Event Data',
            ],
        ),
    ],
)
]
class PresentationActionsAuthSchema
{
}
