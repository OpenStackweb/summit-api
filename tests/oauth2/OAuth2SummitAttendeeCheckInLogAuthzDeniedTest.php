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

/**
 * summit-room-administrators is deliberately NOT an authz group of the attendee check in log endpoints.
 *
 * The harness defaults both the persisted group and the token's IdP groups to administrator, which
 * would short-circuit the 'auth.user' endpoint group check, so both levers are replaced
 * (same approach as PresentationReopenAuthzTest; the duplication is deliberate).
 *
 * Class OAuth2SummitAttendeeCheckInLogAuthzDeniedTest
 * @package Tests
 */
class OAuth2SummitAttendeeCheckInLogAuthzDeniedTest extends ProtectedApiTestCase
{
    use InsertSummitTestData;

    protected function setUp(): void
    {
        // lever 1: persisted group, must precede parent::setUp()
        $this->setCurrentGroup(IGroup::SummitRoomAdministrators);
        parent::setUp();

        // lever 2: IdP groups carried by the token
        self::$service = new AccessTokenServiceStub([['slug' => IGroup::SummitRoomAdministrators]]);
        App::singleton(IAccessTokenService::class, function () { return self::$service; });
        self::$service->setUserId(self::$member->getUserExternalId());
        self::$service->setUserExternalId(self::$member->getUserExternalId());
        self::$service->setUserEmail(self::$member->getEmail());
        self::$service->setUserFirstName(self::$member->getFirstName());
        self::$service->setUserLastName(self::$member->getLastName());

        self::$defaultMember = self::$member;
        self::insertSummitTestData();
    }

    protected function tearDown(): void
    {
        self::clearSummitTestData();
        parent::tearDown();
    }

    private function attendeeId(): int
    {
        $attendee = self::$summit->getAttendeeByMember(self::$defaultMember);
        $this->assertNotNull($attendee);
        return $attendee->getId();
    }

    public function testIdentityIsNotAdmin(): void
    {
        $this->assertFalse(self::$member->isAdmin());
    }

    public function testGetCheckInLogs(): void
    {
        $this->action(
            "GET",
            "OAuth2SummitAttendeeCheckInLogApiController@getAllByAttendee",
            ['id' => self::$summit->getId(), 'attendee_id' => $this->attendeeId()],
            [],
            [],
            [],
            $this->getAuthHeaders()
        );
        $this->assertResponseStatus(403);
    }

    public function testGetCheckInLogsCSV(): void
    {
        $this->action(
            "GET",
            "OAuth2SummitAttendeeCheckInLogApiController@getAllByAttendeeCSV",
            ['id' => self::$summit->getId(), 'attendee_id' => $this->attendeeId()],
            [],
            [],
            [],
            $this->getAuthHeaders()
        );
        $this->assertResponseStatus(403);
    }
}
