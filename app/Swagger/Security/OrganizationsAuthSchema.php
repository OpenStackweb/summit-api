<?php

namespace App\Swagger\schemas;

use App\Security\OrganizationScopes;
use OpenApi\Attributes as OA;


#[OA\SecurityScheme(
    type: 'oauth2',
    securityScheme: 'organizations_oauth2',
    flows: [
        new OA\Flow(
            authorizationUrl: L5_SWAGGER_CONST_AUTH_URL,
            tokenUrl: L5_SWAGGER_CONST_TOKEN_URL,
            flow: 'authorizationCode',
            scopes: [
                OrganizationScopes::WriteOrganizationData => 'Write Organization Data',
                OrganizationScopes::ReadOrganizationData => 'Read Organization Data',
            ],
        ),
    ],
)
]
class OrganizationsAuthSchema
{
}