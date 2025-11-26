<?php

namespace App\Swagger\Security;

use App\Security\SummitScopes;
use OpenApi\Attributes as OA;

#[
    OA\SecurityScheme(
        type: 'oauth2',
        securityScheme: 'summit_submission_invitations_oauth2',
        flows: [
            new OA\Flow(
                authorizationUrl: L5_SWAGGER_CONST_AUTH_URL,
                tokenUrl: L5_SWAGGER_CONST_TOKEN_URL,
                flow: 'authorizationCode',
                scopes: [
                    SummitScopes::ReadAllSummitData => 'Read All Summit Data',
                    SummitScopes::WriteSummitData => 'Write Summit Data',
                    SummitScopes::ReadSubmissionInvitations => 'Read Submission Invitations',
                    SummitScopes::WriteSubmissionInvitations => 'Write Submission Invitations',
                ],
            ),
        ],
    )
]
class SummitSubmissionInvitationsAuthSchema{}
