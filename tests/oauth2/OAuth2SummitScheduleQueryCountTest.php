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

use App\Http\Middleware\Doctrine\QueryTimingCollector;
use App\Models\Foundation\Main\IGroup;
use Illuminate\Support\Facades\App;
use models\main\Member;
use LaravelDoctrine\ORM\Facades\Registry;
use models\oauth2\IResourceServerContext;
use models\summit\Summit;
use models\summit\SummitEvent;
use models\utils\SilverstripeBaseModel;
use ModelSerializers\SerializerRegistry;

/**
 * Class OAuth2SummitScheduleQueryCountTest
 *
 * GET /summits/{id}/members/me/schedule and GET /summits/{id}/attendees/me/schedule serialize every
 * event of the schedule. The relations the schedule preloads for the whole set of events (tags,
 * sponsors, presentation materials and selection status) and the event rows themselves must not be
 * queried once per event: the statements that touch those tables must be the same for a schedule of
 * 10 events and one of 20. The statements are captured off the connection, and the identity map is
 * cleared before each request so the events are loaded the way a real request loads them.
 *
 * Other per-event queries exist (speakers collection, attendance, RSVP, votes, group checks of the
 * current user). They belong to other tickets, so the count of every statement is deliberately not
 * asserted.
 */
class OAuth2SummitScheduleQueryCountTest extends ProtectedApiTestCase
{
    use InsertSummitTestData;

    private const SmallSchedule = 10;

    private const BigSchedule = 20;

    /**
     * Tables behind the relations the schedule preloads, plus the event rows themselves.
     */
    private const PreloadedTables = [
        'SummitEvent',
        'Presentation',
        'Tag',
        'SummitEvent_Tags',
        'SummitEvent_Sponsors',
        'Company',
        'PresentationMaterial',
        'SummitSelectedPresentation',
    ];

    private const Relations = 'sponsors,tags,location,track,type,speakers,slides,links,videos,media_uploads';

    protected function setUp(): void
    {
        $this->current_group = IGroup::TrackChairs;
        parent::setUp();
        self::$defaultMember = self::$member;
        self::insertSummitTestData();
    }

    protected function tearDown(): void
    {
        $this->reloadEntityManager();
        self::clearSummitTestData();
        parent::tearDown();
    }

    /**
     * Each simulated request closes the model entity manager at its end, and the next one opens a
     * new one in the registry, so the instance the test kept is stale after a request.
     */
    private function reloadEntityManager(): void
    {
        self::$em = Registry::getManager(SilverstripeBaseModel::EntityManager);
        if (!self::$em->isOpen()) {
            self::$em = Registry::resetManager(SilverstripeBaseModel::EntityManager);
        }
    }

    /**
     * Puts the first $count published presentations of the fixture on the member's schedule.
     */
    private function fillSchedule(int $count): void
    {
        $this->reloadEntityManager();
        $member = self::$em->find(Member::class, self::$defaultMember->getId());
        $scheduled = 0;
        foreach (self::$presentations as $presentation) {
            if ($scheduled >= $count) break;
            $event = self::$em->find(SummitEvent::class, $presentation->getId());
            if (is_null($event) || !$event->isPublished()) continue;
            if (!$member->isOnSchedule($event)) $member->add2Schedule($event);
            $scheduled++;
        }
        self::$em->flush();
        $this->assertSame($count, $scheduled, 'precondition: the fixture has enough published events');
    }

    /**
     * The resource server context memoizes the current user for the request. The test process serves
     * several requests with the same context, so the Member it kept is detached by the clear() above.
     */
    private function forgetCachedCurrentUser(): void
    {
        // the controllers and the serializer registry may each hold their own context instance
        $registry_property = new \ReflectionProperty(SerializerRegistry::getInstance(), 'resource_server_context');
        $contexts = [App::make(IResourceServerContext::class), $registry_property->getValue(SerializerRegistry::getInstance())];
        foreach ($contexts as $context) {
            foreach (['cachedCurrentUser' => null, 'cachedCurrentUserResolved' => false, 'groupsSynched' => false, 'fieldsSynched' => false] as $property => $value) {
                $reflection = new \ReflectionProperty($context, $property);
                $reflection->setValue($context, $value);
            }
        }
    }

    /**
     * @return array{0: int, 1: array, 2: string[]} number of queries the request issued, decoded payload, captured SQL statements
     */
    private function requestSchedule(string $action, array $params, ?array $query = null): array
    {
        // Evict everything so the request loads the events fresh, like a real one. Fixture entities
        // still hold their in-memory collections, which would hide the lazy loads under test.
        self::$em->clear();
        $this->forgetCachedCurrentUser();

        QueryTimingCollector::$statements = [];
        QueryTimingCollector::reset();
        $response = $this->action(
            "GET",
            $action,
            $params,
            $query ?? ['relations' => self::Relations],
            [],
            [],
            $this->getAuthHeaders()
        );
        $count = QueryTimingCollector::$count;
        $statements = QueryTimingCollector::$statements;
        QueryTimingCollector::$statements = null;
        $this->reloadEntityManager();

        $this->assertResponseStatus(200);
        $payload = json_decode($response->getContent(), true);
        $this->assertIsArray($payload);

        return [$count, $payload, $statements];
    }

    private function tableCounts(array $statements): array
    {
        $counts = [];
        foreach ($statements as $sql) {
            if (!preg_match('/^\s*SELECT\b.*?\bFROM\s+`?(\w+)`?/is', $sql, $m)) continue;
            $counts[$m[1]] = ($counts[$m[1]] ?? 0) + 1;
        }
        return $counts;
    }

    /**
     * @return array<string, int[]> preloaded table => [statements for the small schedule, for the big one]
     */
    private function scalingPreloadedTables(array $small_sql, array $big_sql): array
    {
        $small = $this->tableCounts($small_sql);
        $big = $this->tableCounts($big_sql);
        $scaling = [];
        foreach (self::PreloadedTables as $table) {
            if (($small[$table] ?? 0) !== ($big[$table] ?? 0)) $scaling[$table] = [$small[$table] ?? 0, $big[$table] ?? 0];
        }
        return $scaling;
    }

    /**
     * @return int[]
     */
    private function ids(array $events): array
    {
        return array_map(fn(array $event) => $event['id'], $events);
    }

    public function testMemberSchedulePreloadsRelationsOnceForAllEvents(): void
    {
        $action = "OAuth2SummitMembersApiController@getMemberScheduleSummitEvents";
        $params = ['id' => self::$summit->getId(), 'member_id' => 'me'];

        $this->fillSchedule(self::SmallSchedule);
        $this->requestSchedule($action, $params); // warm-up: metadata and query caches
        [, $small, $small_sql] = $this->requestSchedule($action, $params);
        $this->assertCount(self::SmallSchedule, $small['data']);
        $this->assertNotEmpty($small_sql);

        $this->fillSchedule(self::BigSchedule);
        [, $big, $big_sql] = $this->requestSchedule($action, $params);
        $this->assertCount(self::BigSchedule, $big['data']);

        $this->assertSame(
            [],
            $this->scalingPreloadedTables($small_sql, $big_sql),
            sprintf(
                'The member schedule queried these preloaded tables once per event (statements for %d events, for %d).',
                self::SmallSchedule, self::BigSchedule
            )
        );
    }

    public function testAttendeeSchedulePreloadsRelationsOnceForAllEvents(): void
    {
        $action = "OAuth2SummitAttendeesApiController@getAttendeeSchedule";
        $params = ['id' => self::$summit->getId(), 'attendee_id' => 'me'];

        $this->fillSchedule(self::SmallSchedule);
        $this->requestSchedule($action, $params); // warm-up: metadata and query caches
        [, $small, $small_sql] = $this->requestSchedule($action, $params);
        $this->assertCount(self::SmallSchedule, $small);
        $this->assertNotEmpty($small_sql);

        $this->fillSchedule(self::BigSchedule);
        [, $big, $big_sql] = $this->requestSchedule($action, $params);
        $this->assertCount(self::BigSchedule, $big);

        $this->assertSame(
            [],
            $this->scalingPreloadedTables($small_sql, $big_sql),
            sprintf(
                'The attendee schedule queried these preloaded tables once per event (statements for %d events, for %d).',
                self::SmallSchedule, self::BigSchedule
            )
        );
    }

    /**
     * The schedule of the fixture member serialized the way the endpoints did before the batch
     * preloads: row by row, with nothing preloaded, so every relation is lazy-loaded per event.
     * @return array[]
     */
    private function legacySchedulePayload(): array
    {
        $this->reloadEntityManager();
        self::$em->clear();
        $this->forgetCachedCurrentUser();

        $member = self::$em->find(Member::class, self::$defaultMember->getId());
        $summit = self::$em->find(Summit::class, self::$summit->getId());

        $payload = [];
        foreach ($member->getScheduleBySummit($summit) as $schedule_event) {
            $payload[] = SerializerRegistry::getInstance()->getSerializer($schedule_event)->serialize();
        }
        // same normalization the response goes through: json types, no object instances
        return json_decode(json_encode($payload), true);
    }

    /**
     * Guards the comparison against comparing two empty shapes: the relations the preloads feed
     * have to be in the payload for it to prove anything.
     */
    private function assertPayloadCarriesPreloadedRelations(array $event): void
    {
        foreach (['id', 'tags', 'sponsors', 'speakers', 'media_uploads'] as $key) {
            $this->assertArrayHasKey($key, $event);
        }
    }

    public function testMemberSchedulePayloadIsTheSameAsWithoutPreloads(): void
    {
        $this->fillSchedule(self::SmallSchedule);

        [, $actual] = $this->requestSchedule
        (
            "OAuth2SummitMembersApiController@getMemberScheduleSummitEvents",
            ['id' => self::$summit->getId(), 'member_id' => 'me'],
            []
        );
        $expected = $this->legacySchedulePayload();

        $this->assertCount(self::SmallSchedule, $expected);
        $this->assertPayloadCarriesPreloadedRelations($expected[0]);
        $this->assertEquals($expected, $actual['data']);
    }

    public function testAttendeeSchedulePayloadIsTheSameAsWithoutPreloads(): void
    {
        $this->fillSchedule(self::SmallSchedule);

        [, $actual] = $this->requestSchedule
        (
            "OAuth2SummitAttendeesApiController@getAttendeeSchedule",
            ['id' => self::$summit->getId(), 'attendee_id' => 'me'],
            []
        );
        $expected = $this->legacySchedulePayload();

        $this->assertCount(self::SmallSchedule, $expected);
        $this->assertPayloadCarriesPreloadedRelations($expected[0]);
        $this->assertEquals($expected, $actual);
    }

    /**
     * The handlers used to drop, one by one, the events that are not published through
     * Summit::isEventOnSchedule(). getScheduleBySummit() already filters on e.published, so an event
     * that was published when it was scheduled and was unpublished since must still be left out.
     */
    public function testUnpublishedEventsAreStillLeftOutOfTheSchedule(): void
    {
        $this->fillSchedule(self::SmallSchedule);

        $this->reloadEntityManager();
        self::$em->clear();
        $unpublished = self::$em->find(SummitEvent::class, self::$presentations[0]->getId());
        $unpublished->unPublish();
        self::$em->flush();

        [, $member_schedule] = $this->requestSchedule
        (
            "OAuth2SummitMembersApiController@getMemberScheduleSummitEvents",
            ['id' => self::$summit->getId(), 'member_id' => 'me'],
            []
        );
        [, $attendee_schedule] = $this->requestSchedule
        (
            "OAuth2SummitAttendeesApiController@getAttendeeSchedule",
            ['id' => self::$summit->getId(), 'attendee_id' => 'me'],
            []
        );

        $this->assertCount(self::SmallSchedule - 1, $member_schedule['data']);
        $this->assertCount(self::SmallSchedule - 1, $attendee_schedule);
        $this->assertNotContains($unpublished->getId(), $this->ids($member_schedule['data']));
        $this->assertNotContains($unpublished->getId(), $this->ids($attendee_schedule));
    }

    public function testMemberAndAttendeeSchedulesReturnTheSameEvents(): void
    {
        $this->fillSchedule(self::SmallSchedule);

        [, $member_schedule] = $this->requestSchedule(
            "OAuth2SummitMembersApiController@getMemberScheduleSummitEvents",
            ['id' => self::$summit->getId(), 'member_id' => 'me']
        );
        [, $attendee_schedule] = $this->requestSchedule(
            "OAuth2SummitAttendeesApiController@getAttendeeSchedule",
            ['id' => self::$summit->getId(), 'attendee_id' => 'me']
        );

        $this->assertSame($this->ids($member_schedule['data']), $this->ids($attendee_schedule));
    }
}
