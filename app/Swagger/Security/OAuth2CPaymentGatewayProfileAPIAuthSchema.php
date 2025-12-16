<?php

namespace App\Swagger\schemas;

use App\Security\SummitScopes;
use OpenApi\Attributes as OA;


#[OA\SecurityScheme(
        type: 'oauth2',
        securityScheme: 'summit_payment_gateway_oauth2',
        flows: [
            new OA\Flow(
                authorizationUrl: L5_SWAGGER_CONST_AUTH_URL,
                tokenUrl: L5_SWAGGER_CONST_TOKEN_URL,
                flow: 'authorizationCode',
                scopes: [
                    SummitScopes::ReadAllSummitData => 'Read all summit data',
                    SummitScopes::ReadPaymentProfiles => 'Read payment profiles',
                    SummitScopes::WriteSummitData => 'Write summit data',
                    SummitScopes::WritePaymentProfiles => 'Write payment profiles',
                ],
            ),
        ],
    )
]
class OAuth2CPaymentGatewayProfileAPIAuthSchema{}
