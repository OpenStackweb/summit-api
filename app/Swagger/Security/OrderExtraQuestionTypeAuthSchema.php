<?php

namespace App\Swagger\schemas;

use App\Security\SummitScopes;
use OpenApi\Attributes as OA;

#[OA\SecurityScheme(
    type: 'oauth2',
    securityScheme: 'order_extra_questions_oauth2',
    description: 'OAuth2 authentication for Order Extra Questions endpoints',
    flows: [
        new OA\Flow(
            authorizationUrl: '/oauth/authorize',
            tokenUrl: '/oauth/token',
            refreshUrl: '/oauth/refresh',
            flow: 'authorizationCode',
            scopes: [
                SummitScopes::ReadSummitData => 'Read Summit Data',
                SummitScopes::ReadAllSummitData => 'Read All Summit Data',
                SummitScopes::WriteSummitData => 'Write Summit Data',
            ]
        )
    ]
)]
class OrderExtraQuestionTypeAuthSchema {}
