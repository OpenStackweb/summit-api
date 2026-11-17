<?php namespace Tests;
/**
 * Copyright 2026 OpenStack Foundation
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 * http://www.apache.org/licenses/LICENSE-2.0
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 **/

use App\Models\Foundation\Main\IGroup;
use App\Models\ResourceServer\IAccessTokenService;
use Illuminate\Support\Facades\App;
use models\summit\SummitSelectedPresentation;

/**
 * Team selection lists ("Track Chairs > Team Lists" in summit-admin) used by a summit admin.
 *
 * The identity is a summit admin of THIS summit only: persisted in the summit administrators
 * group and member of the summit's permission group, but not a global admin and not a track
 * chair. The token stub is replaced because its default IdP groups include administrators,
 * which would make every request pass the auth.user middleware regardless of the endpoint's
 * authz groups.
 *
 * Class OAuth2SummitSelectedPresentationListSummitAdminApiTest
 * @package Tests
 */
final class OAuth2SummitSelectedPresentationListSummitAdminApiTest extends ProtectedApiTestCase
{
    use InsertSummitTestData;

    protected function setUp(): void
    {
        $this->setCurrentGroup(IGroup::CommunityMembers);
        parent::setUp();

        self::$service = new AccessTokenServiceStub([['slug' => IGroup::BadgePrinters]]);
        App::singleton(IAccessTokenService::class, function () { return self::$service; });

        self::$service->setUserId(self::$member->getUserExternalId());
        self::$service->setUserExternalId(self::$member->getUserExternalId());
        self::$service->setUserEmail(self::$member->getEmail());
        self::$service->setUserFirstName(self::$member->getFirstName());
        self::$service->setUserLastName(self::$member->getLastName());

        self::insertSummitTestData();

        self::$member->add2Group(self::$group2); // summit administrators
        self::$em->persist(self::$member);
        self::$em->flush();

        $this->assertFalse(self::$member->isAdmin(true), 'fixture member must not be a global admin');
        $this->assertNull(self::$member->getGroupByCode(IGroup::TrackChairs), 'fixture member must not be a track chair');
    }

    protected function tearDown(): void
    {
        self::clearSummitTestData();
        parent::tearDown();
    }

    private function grantSummitAdminPermission(): void
    {
        self::$summit_permission_group->addMember(self::$member);
        self::$em->persist(self::$summit_permission_group);
        self::$em->flush();
    }

    private function headers(): array
    {
        return [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"       => "application/json"
        ];
    }

    private function trackParams(): array
    {
        return [
            'id'                => self::$summit->getId(),
            'selection_plan_id' => self::$default_selection_plan->getId(),
            'track_id'          => self::$defaultTrack->getId(),
        ];
    }

    public function testSummitAdminCanCreateGetAndReorderTeamSelectionList(): void
    {
        $this->grantSummitAdminPermission();

        $this->action(
            "POST",
            "OAuth2SummitSelectedPresentationListApiController@createTeamSelectionList",
            $this->trackParams(),
            [],
            [],
            [],
            $this->headers(),
            ""
        );
        $this->assertResponseStatus(201);

        $response = $this->action(
            "GET",
            "OAuth2SummitSelectedPresentationListApiController@getTeamSelectionList",
            $this->trackParams(),
            [],
            [],
            [],
            $this->headers()
        );
        $this->assertResponseStatus(200);
        $team_list = json_decode($response->getContent());
        $this->assertNotNull($team_list);
        $this->assertEquals('Group', $team_list->type);

        $presentations = [
            self::$presentations[1]->getId(),
            self::$presentations[0]->getId(),
            self::$presentations[2]->getId(),
        ];

        $response = $this->action(
            "PUT",
            "OAuth2SummitSelectedPresentationListApiController@reorderSelectionList",
            array_merge($this->trackParams(), ['list_id' => $team_list->id, 'expand' => 'selected_presentations']),
            [],
            [],
            [],
            $this->headers(),
            json_encode([
                'collection'    => SummitSelectedPresentation::CollectionSelected,
                'presentations' => $presentations,
                'hash'          => $team_list->hash ?? '',
            ])
        );
        $this->assertResponseStatus(201);
        $team_list = json_decode($response->getContent());
        $this->assertCount(3, $team_list->selected_presentations);
    }

    public function testSummitAdminOfAnotherSummitCannotGetTeamSelectionList(): void
    {
        // summit administrators group, but no permission group linking the member to this summit
        $this->action(
            "GET",
            "OAuth2SummitSelectedPresentationListApiController@getTeamSelectionList",
            $this->trackParams(),
            [],
            [],
            [],
            $this->headers()
        );
        $this->assertResponseStatus(403);
    }
}
