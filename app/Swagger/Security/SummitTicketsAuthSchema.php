<?php

namespace App\Swagger\schemas;

use App\Security\SummitScopes;
use OpenApi\Attributes as OA;

#[OA\SecurityScheme(
    type: 'oauth2',
    securityScheme: 'summit_tickets_oauth2',
    flows: [
        new OA\Flow(
            authorizationUrl: L5_SWAGGER_CONST_AUTH_URL,
            tokenUrl: L5_SWAGGER_CONST_TOKEN_URL,
            flow: 'authorizationCode',
            scopes: [
                SummitScopes::ReadAllSummitData => 'Read All Summit Data',
                SummitScopes::ReadRegistrationOrders => 'Read Registration Orders',
                SummitScopes::WriteSummitData => 'Write Summit Data',
                SummitScopes::WriteRegistrationData => 'Write Registration Data',
                SummitScopes::UpdateRegistrationOrders => 'Update Registration Orders',
                SummitScopes::UpdateRegistrationOrdersBadges => 'Update Registration Orders Badges',
                SummitScopes::PrintRegistrationOrdersBadges => 'Print Registration Orders Badges',
                SummitScopes::ReadMyRegistrationOrders => 'Read My Registration Orders',
            ],
        ),
    ],
)]
class SummitTicketsAuthSchema {}
