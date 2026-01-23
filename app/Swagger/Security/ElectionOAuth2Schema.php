<?php

namespace App\Swagger\schemas;

use App\Security\ElectionScopes;
use OpenApi\Attributes as OA;

#[
    OA\SecurityScheme(
        type: 'oauth2',
        securityScheme: 'election_oauth2',
        flows: [
            new OA\Flow(
                authorizationUrl: L5_SWAGGER_CONST_AUTH_URL,
                tokenUrl: L5_SWAGGER_CONST_TOKEN_URL,
                flow: 'authorizationCode',
                scopes: [
                    ElectionScopes::ReadAllElections => 'Read All Elections',
                    ElectionScopes::WriteMyCandidateProfile => 'Write My Candidate Profile',
                    ElectionScopes::NominatesCandidates => 'Nominate Candidates',
                ],
            ),
        ],
    )
]
class ElectionOAuth2Schema {}
