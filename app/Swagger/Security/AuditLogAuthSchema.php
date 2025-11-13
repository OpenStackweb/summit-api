<?php

namespace App\Swagger\Schemas;

use OpenApi\Attributes as OA;
use App\Security\SummitScopes;

#[OA\SecurityScheme(
    type: 'oauth2',
    securityScheme: 'audit_logs_oauth2',
    flows: [
        new OA\Flow(
            authorizationUrl: L5_SWAGGER_CONST_AUTH_URL,
            tokenUrl: L5_SWAGGER_CONST_TOKEN_URL,
            flow: 'authorizationCode',
            scopes: [
                SummitScopes::ReadAuditLogs,
            ],
        ),
    ],
)
]
class AuditLogAuthSchema
{
}