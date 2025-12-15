<?php

namespace App\Swagger\schemas;

use App\Security\SummitScopes;
use OpenApi\Attributes as OA;

#[
    OA\Info(version: "1.0.0", description: "Summit API", title: "Summit API Documentation"),
    OA\Server(url: L5_SWAGGER_CONST_HOST, description: "server"),
    OA\SecurityScheme(
    type: 'oauth2',
    securityScheme: 'summit_oauth2',
    flows: [
        new OA\Flow(
            authorizationUrl: L5_SWAGGER_CONST_AUTH_URL,
            tokenUrl: L5_SWAGGER_CONST_TOKEN_URL,
            flow: 'authorizationCode',
            scopes: [
                SummitScopes::ReadSummitData => 'Read Summit Data',
                SummitScopes::ReadAllSummitData => 'Read All Summit Data',
                SummitScopes::WriteSummitData => 'Write Summit Data',
                SummitScopes::ReadBadgeScanValidate => 'Validate Badge Scan',
            ],
        ),
    ],
)
]

class SummitAuthSchema
{
}