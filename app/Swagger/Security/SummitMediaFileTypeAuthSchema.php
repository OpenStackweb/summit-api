<?php

namespace App\Swagger\schemas;

use App\Security\SummitScopes;
use OpenApi\Attributes as OA;



#[OA\SecurityScheme(
        type: 'oauth2',
        securityScheme: 'summit_media_file_type_oauth2',
        flows: [
            new OA\Flow(
                authorizationUrl: L5_SWAGGER_CONST_AUTH_URL,
                tokenUrl: L5_SWAGGER_CONST_TOKEN_URL,
                flow: 'authorizationCode',
                scopes: [
                    SummitScopes::ReadSummitMediaFileTypes => 'Read Summit Media File Types',
                    SummitScopes::WriteSummitMediaFileTypes => 'Write Summit Media File Types',
                ],
            ),
        ],
    )
]
class SummitMediaFileTypeAuthSchema{}
