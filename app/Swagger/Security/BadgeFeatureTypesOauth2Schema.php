<?php

namespace App\Swagger\schemas;

use App\Security\SummitScopes;
use OpenApi\Attributes as OA;

#[OA\SecurityScheme(
        type: 'oauth2',
        securityScheme: 'badge_feature_types_oauth2',
        flows: [
            new OA\Flow(
                authorizationUrl: L5_SWAGGER_CONST_AUTH_URL,
                tokenUrl: L5_SWAGGER_CONST_TOKEN_URL,
                flow: 'authorizationCode',
                scopes: [
                    SummitScopes::ReadAllSummitData => 'Read All Summit Data',
                    SummitScopes::ReadSummitData => 'Read Summit Data',
                    SummitScopes::WriteSummitData => 'Write Summit Data',
                ],
            ),
        ],
    )
]
class BadgeFeatureTypesOauth2Schema{}
