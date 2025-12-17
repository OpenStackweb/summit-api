<?php

namespace App\Swagger\schemas;

use App\Security\SummitScopes;
use OpenApi\Attributes as OA;


#[OA\SecurityScheme(
    type: 'oauth2',
    securityScheme: 'summit_orders_auth',
    flows: [
        new OA\Flow(
            authorizationUrl: L5_SWAGGER_CONST_AUTH_URL,
            tokenUrl: L5_SWAGGER_CONST_TOKEN_URL,
            flow: 'authorizationCode',
            scopes: [
                SummitScopes::ReadAllSummitData => 'Read All Summit Data',
                SummitScopes::ReadRegistrationOrders => 'Read Registration Orders',
                SummitScopes::ReadMyRegistrationOrders => 'Read My Registration Orders',
                SummitScopes::WriteSummitData => 'Write Summit Data',
                SummitScopes::CreateRegistrationOrders => 'Create Registration Orders',
                SummitScopes::CreateOfflineRegistrationOrders => 'Create Offline Registration Orders',
                SummitScopes::DeleteRegistrationOrders => 'Delete Registration Orders',
                SummitScopes::UpdateRegistrationOrders => 'Update Registration Orders',
                SummitScopes::UpdateMyRegistrationOrders => 'Update My Registration Orders',
                SummitScopes::DeleteMyRegistrationOrders => 'Delete My Registration Orders',
            ],
        ),
    ],
)
]
class OAuth2SummitOrdersApiControllerAuthSchema{}
