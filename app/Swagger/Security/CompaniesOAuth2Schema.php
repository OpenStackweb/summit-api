<?php

namespace App\Swagger\schemas;

use App\Security\CompanyScopes;
use App\Security\SummitScopes;
use OpenApi\Attributes as OA;

#[OA\SecurityScheme(
    type: 'oauth2',
    securityScheme: 'companies_oauth2',
    flows: [
        new OA\Flow(
            authorizationUrl: L5_SWAGGER_CONST_AUTH_URL,
            tokenUrl: L5_SWAGGER_CONST_TOKEN_URL,
            flow: 'authorizationCode',
            scopes: [
                CompanyScopes::Read => 'Read Data',
                CompanyScopes::Write => 'Write Data',
                SummitScopes::ReadSummitData => 'Read Summit Data',
                SummitScopes::ReadAllSummitData => 'Read All Summit Data',
                SummitScopes::WriteSummitData => 'Write Summit Data',
            ],
        ),
    ],
)
]
class CompaniesOAuth2Schema
{
}
