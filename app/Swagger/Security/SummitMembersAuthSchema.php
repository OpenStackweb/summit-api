<?php

namespace App\Swagger\schemas;

use App\Security\MemberScopes;
use App\Security\SummitScopes;
use OpenApi\Attributes as OA;

#[
    OA\SecurityScheme(
        type: 'oauth2',
        securityScheme: 'summit_members_oauth2',
        flows: [
            new OA\Flow(
                authorizationUrl: L5_SWAGGER_CONST_AUTH_URL,
                tokenUrl: L5_SWAGGER_CONST_TOKEN_URL,
                flow: 'authorizationCode',
                scopes: [
                    SummitScopes::MeRead => 'Read My Member Data',
                    MemberScopes::ReadMyMemberData => 'Read My Member Data',
                    SummitScopes::AddMyFavorites => 'Add Favorites to My Schedule',
                    SummitScopes::DeleteMyFavorites => 'Remove Favorites from My Schedule',
                    SummitScopes::AddMySchedule => 'Add Events to My Schedule',
                    SummitScopes::DeleteMySchedule => 'Remove Events from My Schedule',
                    SummitScopes::AddMyScheduleShareable => 'Create Shareable Links for My Schedule',
                    SummitScopes::DeleteMyScheduleShareable => 'Delete Shareable Links for My Schedule',
                    SummitScopes::WriteSummitData => 'Write Summit Data',
                    SummitScopes::ReadAllSummitData => 'Read All Summit Data',
                    SummitScopes::ReadSummitData => 'Read Summit Data',
                ],
            ),
        ],
    )
]
class SummitMembersAuthSchema {}
