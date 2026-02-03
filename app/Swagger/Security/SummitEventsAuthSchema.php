<?php

namespace App\Swagger\schemas;

use App\Security\MemberScopes;
use App\Security\SummitScopes;
use OpenApi\Attributes as OA;

#[
    OA\SecurityScheme(
        type: 'oauth2',
        securityScheme: 'summit_events_api_oauth2',
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
                    SummitScopes::PublishEventData => 'Publish Event Data',
                    SummitScopes::AddMyEventFeedback => 'Add My Event Feedback',
                    SummitScopes::DeleteMyEventFeedback => 'Delete My Event Feedback',
                    SummitScopes::SendMyScheduleMail => 'Send My Schedule Mail',
                    SummitScopes::MeRead => 'Me Read',
                    MemberScopes::ReadMyMemberData => 'Read My Member Data',
                ],
            ),
        ],
    )
]
class SummitEventsAuthSchema {}
