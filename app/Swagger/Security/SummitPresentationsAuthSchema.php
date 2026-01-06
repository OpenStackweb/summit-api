<?php

namespace App\Swagger\Security;

use App\Security\SummitScopes;
use OpenApi\Attributes as OA;

#[
    OA\SecurityScheme(
        type: 'oauth2',
        securityScheme: 'summit_presentations_auth',
        flows: [
            new OA\Flow(
                authorizationUrl: L5_SWAGGER_CONST_AUTH_URL,
                tokenUrl: L5_SWAGGER_CONST_TOKEN_URL,
                flow: 'authorizationCode',
                scopes: [
                    SummitScopes::ReadSummitData => 'Read Summit Data',
                    SummitScopes::ReadAllSummitData => 'Read All Summit Data',
                    SummitScopes::WriteSummitData => 'Write Summit Data',
                    SummitScopes::WriteEventData => 'Write Event Data',
                    SummitScopes::WritePresentationData => 'Write Presentation Data',
                    SummitScopes::WriteVideoData => 'Write Video Data',
                    SummitScopes::WritePresentationVideosData => 'Write Presentation Videos Data',
                    SummitScopes::WritePresentationLinksData => 'Write Presentation Links Data',
                    SummitScopes::WritePresentationSlidesData => 'Write Presentation Slides Data',
                    SummitScopes::WritePresentationMaterialsData => 'Write Presentation Materials Data',
                    SummitScopes::WriteSpeakersData => 'Write Speakers Data',
                    SummitScopes::Allow2PresentationAttendeeVote => 'Attendee Vote',
                ],
            ),
        ],
    )
]
class SummitPresentationsAuthSchema {}
