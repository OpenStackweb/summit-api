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
use models\summit\SummitEvent;

/**
 * A track chair allowed on the summit must keep getting the public event serializer:
 * only room administrators gain the admin payload through summit permission groups.
 * Class OAuth2SummitTrackChairEventSerializerTest
 * @package Tests
 */
class OAuth2SummitTrackChairEventSerializerTest extends ProtectedApiTestCase
{
    use InsertSummitTestData;

    /**
     * @var SummitEvent
     */
    static $event;

    protected function setUp(): void
    {
        $this->setCurrentGroup(IGroup::TrackChairs);
        parent::setUp();

        // the default stub reports the administrators IdP group, which would make isAdmin() true
        self::$service = new AccessTokenServiceStub([['slug' => IGroup::TrackChairs]]);
        App::singleton(IAccessTokenService::class, function () { return self::$service; });
        self::$service->setUserId(self::$member->getUserExternalId());
        self::$service->setUserExternalId(self::$member->getUserExternalId());
        self::$service->setUserEmail(self::$member->getEmail());
        self::$service->setUserFirstName(self::$member->getFirstName());
        self::$service->setUserLastName(self::$member->getLastName());

        self::insertSummitTestData();

        self::$summit_permission_group->addMember(self::$member);

        $start_date = clone(self::$summit->getBeginDate());
        $end_date = (clone $start_date)->add(new \DateInterval("PT1H"));

        self::$event = new SummitEvent();
        self::$event->setTitle(sprintf("Track Chair Serializer Event %s", str_random(16)));
        self::$event->setAbstract(sprintf("Track Chair Serializer Event Abstract %s", str_random(16)));
        self::$event->setCategory(self::$defaultTrack);
        self::$event->setType(self::$defaultEventType);
        self::$summit->addEvent(self::$event);
        self::$event->setStartDate($start_date);
        self::$event->setEndDate($end_date);
        self::$event->publish();
        self::$event->setOverflow("https://testoverflow-trackchair.org", true);
        self::$em->persist(self::$event);
        self::$em->persist(self::$summit_permission_group);
        self::$em->flush();

        $this->assertFalse(self::$member->isAdmin(true));
        $this->assertTrue(self::$member->isSummitAllowed(self::$summit));
    }

    protected function tearDown(): void
    {
        self::clearSummitTestData();
        parent::tearDown();
    }

    public function testTrackChairAllowedOnSummitGetsPublicEvent()
    {
        $response = $this->action(
            "GET",
            "OAuth2SummitEventsApiController@getEvent",
            ['id' => self::$summit->getId(), 'event_id' => self::$event->getId()],
            [], [], [],
            $this->getAuthHeaders()
        );
        $this->assertResponseStatus(200);

        $event = json_decode($response->getContent());
        $this->assertFalse(property_exists($event, 'overflow_streaming_url'));
        $this->assertFalse(property_exists($event, 'overflow_stream_is_secure'));
        $this->assertFalse(property_exists($event, 'streaming_url'));
    }
}
