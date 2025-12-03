<?php

namespace App\Swagger\Security;

use App\Security\SummitScopes;
use OpenApi\Attributes as OA;

#[
    OA\SecurityScheme(
        type: 'oauth2',
        securityScheme: 'summit_attendees_oauth2',
        flows: [
            new OA\Flow(
                authorizationUrl: L5_SWAGGER_CONST_AUTH_URL,
                tokenUrl: L5_SWAGGER_CONST_TOKEN_URL,
                flow: 'authorizationCode',
                scopes: [
                    SummitScopes::ReadSummitData => 'Read Summit Data',
                    SummitScopes::ReadAllSummitData => 'Read All Summit Data',
                    SummitScopes::WriteSummitData => 'Write Summit Data',
                    SummitScopes::WriteAttendeesData => 'Write Attendees Data',
                    SummitScopes::DoVirtualCheckIn => 'Do Virtual Check-In',
                    SummitScopes::DeleteMyRSVP => 'Delete My RSVP',
                ],
            ),
        ],
    )
]
class SummitAttendeesAuthSchema {}
