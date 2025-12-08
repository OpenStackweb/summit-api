<?php

namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;

#[OA\SecurityScheme(
    type: 'rsvp_templates_oauth2',
    securityScheme: 'summit_rsvp_templates_oauth2',
    flows: [
        new OA\Flow(
            authorizationUrl: L5_SWAGGER_CONST_AUTH_URL,
            tokenUrl: L5_SWAGGER_CONST_TOKEN_URL,
            flow: 'authorizationCode',
            scopes: [
                SummitScopes::ReadAllSummitData => 'Read All Summit Data',
                SummitScopes::WriteSummitData => 'Write Summit Data',
                SummitScopes::WriteRSVPTemplateData => 'Write RSVP Template Data',
            ],
        ),
    ],
)
]
class RSVPTemplatesAuthSchema
{
}
