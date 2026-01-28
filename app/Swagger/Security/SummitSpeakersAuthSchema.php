<?php

namespace App\Swagger\schemas;

use App\Security\SummitScopes;
use OpenApi\Attributes as OA;

#[
    OA\SecurityScheme(
        type: 'oauth2',
        securityScheme: 'summit_speakers_oauth2',
        flows: [
            new OA\Flow(
                authorizationUrl: L5_SWAGGER_CONST_AUTH_URL,
                tokenUrl: L5_SWAGGER_CONST_TOKEN_URL,
                flow: 'authorizationCode',
                scopes: [
                    SummitScopes::ReadSummitData => 'Read Summit Data',
                    SummitScopes::ReadAllSummitData => 'Read All Summit Data',
                    SummitScopes::WriteSummitData => 'Write Summit Data',
                    SummitScopes::ReadSpeakersData => 'Read Speakers Data',
                    SummitScopes::WriteSpeakersData => 'Write Speakers Data',
                    SummitScopes::ReadMySpeakersData => 'Read My Speakers Data',
                    SummitScopes::WriteMySpeakersData => 'Write My Speakers Data',
                ],
            ),
        ],
    )
]
class SummitSpeakersAuthSchema {}
