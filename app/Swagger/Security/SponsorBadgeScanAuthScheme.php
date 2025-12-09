<?php

namespace App\Swagger\schemas;

use App\Security\SummitScopes;
use OpenApi\Attributes as OA;


#[
    OA\SecurityScheme(
        type: 'oauth2',
        securityScheme: 'summit_badge_scan_oauth2',
        flows: [
            new OA\Flow(
                authorizationUrl: L5_SWAGGER_CONST_AUTH_URL,
                tokenUrl: L5_SWAGGER_CONST_TOKEN_URL,
                flow: 'authorizationCode',
                scopes: [
                    SummitScopes::ReadBadgeScan => 'Read Badge Scan Data',
                    SummitScopes::ReadMyBadgeScan => 'Read My Badge Scan Data',
                    SummitScopes::ReadBadgeScanValidate => 'Validate Badge Scan',
                    SummitScopes::ReadAllSummitData => 'Read All Summit Data',
                    SummitScopes::WriteSummitData => 'Write Summit Data',
                    SummitScopes::WriteBadgeScan => 'Write Badge Scan Data',
                    SummitScopes::WriteMyBadgeScan => 'Write My Badge Scan Data',
                ],
            ),
        ],
    )
]
class SponsorBadgeScanAuthScheme
{
}
