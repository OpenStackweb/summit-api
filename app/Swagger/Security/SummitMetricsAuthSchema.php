<?php

namespace App\Swagger\schemas;

use App\Security\SummitScopes;
use OpenApi\Attributes as OA;

#[
    OA\SecurityScheme(
        type: 'oauth2',
        securityScheme: 'summit_metrics_oauth2',
        flows: [
            new OA\Flow(
                authorizationUrl: L5_SWAGGER_CONST_AUTH_URL,
                tokenUrl: L5_SWAGGER_CONST_TOKEN_URL,
                flow: 'authorizationCode',
                scopes: [
                    SummitScopes::EnterEvent => 'Enter Event',
                    SummitScopes::LeaveEvent => 'Leave Event',
                    SummitScopes::WriteMetrics => 'Write Metrics',
                    SummitScopes::ReadMetrics => 'Read Metrics',
                    SummitScopes::ReadAllSummitData => 'Read All Summit Data',
                    SummitScopes::ReadSummitData => 'Read Summit Data',
                ],
            ),
        ],
    )
]
class SummitMetricsAuthSchema {}
