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
use Illuminate\Support\Facades\Queue;
use LaravelDoctrine\ORM\Facades\Registry;
use models\summit\SummitEvent;
use models\utils\SilverstripeBaseModel;

/**
 * setOverflow / clearOverflow must only be allowed on summits the caller has access to.
 * The identity is a summit-room-administrators member (not a global admin) whose permission
 * group is scoped to self::$summit only.
 * Class OAuth2SummitEventsOverflowAuthzTest
 * @package Tests
 */
class OAuth2SummitEventsOverflowAuthzTest extends ProtectedApiTestCase
{
    use InsertSummitTestData;

    const OriginalOverflowUrl = "https://testoverflow-original.org";

    /**
     * @var SummitEvent
     */
    static $allowed_event;

    /**
     * @var SummitEvent
     */
    static $other_summit_event;

    protected function setUp(): void
    {
        $this->setCurrentGroup(IGroup::SummitRoomAdministrators);
        parent::setUp();

        // the default stub reports the administrators IdP group, which would make isAdmin() true
        self::$service = new AccessTokenServiceStub([['slug' => IGroup::SummitRoomAdministrators]]);
        App::singleton(IAccessTokenService::class, function () { return self::$service; });
        self::$service->setUserId(self::$member->getUserExternalId());
        self::$service->setUserExternalId(self::$member->getUserExternalId());
        self::$service->setUserEmail(self::$member->getEmail());
        self::$service->setUserFirstName(self::$member->getFirstName());
        self::$service->setUserLastName(self::$member->getLastName());

        self::insertSummitTestData();

        // scope the member to self::$summit only
        self::$summit_permission_group->addMember(self::$member);

        self::$allowed_event = $this->createOverflowEvent(self::$summit);
        self::$other_summit_event = $this->createOverflowEvent(self::$summit2);

        self::$em->persist(self::$summit_permission_group);
        self::$em->flush();

        $this->assertFalse(self::$member->isAdmin(true));
        $this->assertTrue(self::$member->isSummitAllowed(self::$summit));
        $this->assertFalse(self::$member->isSummitAllowed(self::$summit2));
    }

    protected function tearDown(): void
    {
        self::clearSummitTestData();
        parent::tearDown();
    }

    private function createOverflowEvent($summit): SummitEvent
    {
        $start_date = clone($summit->getBeginDate());
        $end_date = (clone $start_date)->add(new \DateInterval("PT1H"));

        $event = new SummitEvent();
        $event->setTitle(sprintf("Overflow Authz Event %s", str_random(16)));
        $event->setAbstract(sprintf("Overflow Authz Event Abstract %s", str_random(16)));
        $event->setCategory(self::$defaultTrack);
        $event->setType(self::$defaultEventType);
        $summit->addEvent($event);
        $event->setStartDate($start_date);
        $event->setEndDate($end_date);
        $event->publish();
        $event->setOverflow(self::OriginalOverflowUrl, true);
        self::$em->persist($event);
        return $event;
    }

    private function reload(int $id): ?SummitEvent
    {
        self::$em = Registry::getManager(SilverstripeBaseModel::EntityManager);
        if (!self::$em->isOpen()) {
            self::$em = Registry::resetManager(SilverstripeBaseModel::EntityManager);
        }
        self::$em->clear();
        return self::$em->getRepository(SummitEvent::class)->find($id);
    }

    private function setOverflow($summit, SummitEvent $event)
    {
        return $this->action(
            "PUT",
            "OAuth2SummitEventsApiController@setOverflow",
            ['id' => $summit->getId(), 'event_id' => $event->getId()],
            [], [], [],
            $this->getAuthHeaders(),
            json_encode([
                'overflow_streaming_url' => 'https://testoverflow-updated.org',
                'overflow_stream_is_secure' => false,
            ])
        );
    }

    private function clearOverflow($summit, SummitEvent $event)
    {
        return $this->action(
            "DELETE",
            "OAuth2SummitEventsApiController@clearOverflow",
            ['id' => $summit->getId(), 'event_id' => $event->getId()],
            [], [], [],
            $this->getAuthHeaders(),
            json_encode([])
        );
    }

    public function testRoomAdministratorCanSetOverflowOnAllowedSummit()
    {
        Queue::fake();
        $this->setOverflow(self::$summit, self::$allowed_event);
        $this->assertResponseStatus(201);

        $event = $this->reload(self::$allowed_event->getId());
        $this->assertEquals('https://testoverflow-updated.org', $event->getOverflowStreamingUrl());
    }

    public function testRoomAdministratorCanClearOverflowOnAllowedSummit()
    {
        Queue::fake();
        $this->clearOverflow(self::$summit, self::$allowed_event);
        $this->assertResponseStatus(201);

        $event = $this->reload(self::$allowed_event->getId());
        $this->assertEquals(SummitEvent::OccupancyEmpty, $event->getOccupancy());
    }

    public function testRoomAdministratorCannotSetOverflowOnAnotherSummit()
    {
        Queue::fake();
        $this->setOverflow(self::$summit2, self::$other_summit_event);
        $this->assertResponseStatus(403);

        $event = $this->reload(self::$other_summit_event->getId());
        $this->assertEquals(self::OriginalOverflowUrl, $event->getOverflowStreamingUrl());
    }

    public function testRoomAdministratorCannotClearOverflowOnAnotherSummit()
    {
        Queue::fake();
        $this->clearOverflow(self::$summit2, self::$other_summit_event);
        $this->assertResponseStatus(403);

        $event = $this->reload(self::$other_summit_event->getId());
        $this->assertEquals(SummitEvent::OccupancyOverflow, $event->getOccupancy());
    }
}
