<?php

namespace App\Swagger\security;

use App\Security\SummitScopes;
use OpenApi\Attributes as OA;


#[
    OA\SecurityScheme(
        type: 'oauth2',
        securityScheme: 'summit_attendee_notes_oauth2',
        flows: [
            new OA\Flow(
                authorizationUrl: L5_SWAGGER_CONST_AUTH_URL,
                tokenUrl: L5_SWAGGER_CONST_TOKEN_URL,
                flow: 'authorizationCode',
                scopes: [
                    SummitScopes::ReadAllSummitData => 'Read All Summit Data',
                    SummitScopes::ReadAttendeeNotesData => 'Read Attendee Notes Data',
                    SummitScopes::WriteSummitData => 'Write Summit Data',
                    SummitScopes::WriteAttendeeNotesData => 'Write Attendee Notes Data',
                ],
            ),
        ],
    )
]
class SummitAttendeeNotesSecurityScheme
{
}
