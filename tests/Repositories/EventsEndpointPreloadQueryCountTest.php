<?php namespace Tests\Repositories;
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
use models\summit\ISummitEventRepository;
use models\summit\Presentation;
use models\summit\PresentationSpeaker;
use Tests\InsertSummitTestData;
use Tests\ProtectedApiTestCase;
use utils\FilterParser;
use utils\PagingInfo;

/**
 * Integration / query-count regression test for the /events N+1 elimination
 * (PR #549, hotfix/cache-optimizations).
 *
 * Lives under tests/Repositories/ so it runs in the CI "Repositories" matrix job.
 *
 * Unlike PresentationSpeakerCacheTest (which proves the preload helpers in
 * isolation), this test drives the real DoctrineSummitEventRepository::getAllByPage
 * path — the same path the /events endpoint uses — and asserts that the
 * per-speaker work the serializer does does NOT scale with the number of
 * distinct speakers/members on the page.
 *
 * IMPORTANT seed note: the base InsertSummitTestData shares ONE speaker across
 * all presentations, and that speaker's member is the authenticated user (already
 * in the identity map). That cannot reproduce the member/order N+1, which only
 * appears with DISTINCT speakers. So this test attaches several distinct speakers
 * (each with its own member) to distinct presentations, then clears the identity
 * map so getAllByPage must load everything fresh — exactly like a real request.
 *
 * The guarded property: after getAllByPage's preload (LEFT JOIN s.member m +
 * assignment fetch), the serializer's per-speaker accessors — getMember() field
 * access and getPresentationAssignmentOrder() — fire ZERO additional queries even
 * across distinct speakers. Without the preload, each distinct speaker forces a
 * lazy member-proxy init plus a per-(speaker,presentation) assignment-order query,
 * so this count would scale with the number of speakers.
 */
final class EventsEndpointPreloadQueryCountTest extends ProtectedApiTestCase
{
    use InsertSummitTestData;

    private const DISTINCT_SPEAKERS = 5;

    protected function setUp(): void
    {
        $this->current_group = IGroup::TrackChairs;
        parent::setUp();
        self::insertSummitTestData();
    }

    public function tearDown(): void
    {
        self::clearSummitTestData();
        parent::tearDown();
    }

    public function testEventsPagePreloadDoesNotScaleQueriesWithDistinctSpeakers(): void
    {
        $em = self::$em;

        // Attach N distinct speakers (each with its own, otherwise-unloaded member)
        // to N distinct seeded presentations.
        $targetIds = [];
        foreach (self::$presentations as $presentation) {
            if (!($presentation instanceof Presentation)) continue;

            $suffix = str_random(8);
            $member = new Member();
            $member->setEmail(sprintf("nplus1+%s@example.test", $suffix));
            $member->setActive(true);
            $member->setFirstName("NPlusOne");
            $member->setLastName($suffix);
            $member->setEmailVerified(true);
            $member->setUserExternalId(mt_rand());
            $em->persist($member);

            $speaker = new PresentationSpeaker();
            $speaker->setFirstName("NPlusOne");
            $speaker->setLastName($suffix);
            $speaker->setBio("bio");
            $speaker->setMember($member);
            $em->persist($speaker);

            $presentation->addSpeaker($speaker);
            $targetIds[] = $presentation->getId();

            if (count($targetIds) >= self::DISTINCT_SPEAKERS) break;
        }
        $em->flush();
        $this->assertCount(self::DISTINCT_SPEAKERS, $targetIds, 'precondition: distinct speakers attached');

        $summitId = self::$summit->getId();

        // Evict everything so getAllByPage loads fresh — otherwise the new members
        // would already be managed and getMember() would never need a query.
        $em->clear();

        /** @var ISummitEventRepository $repo */
        $repo = App::make(ISummitEventRepository::class);
        $filter = FilterParser::parse(['summit_id==' . $summitId], ['summit_id' => ['==']]);

        $response = $repo->getAllByPage(new PagingInfo(1, 100), $filter);

        $byId = [];
        foreach ($response->getItems() as $event) {
            if ($event instanceof Presentation) $byId[$event->getId()] = $event;
        }
        $targets = [];
        foreach ($targetIds as $pid) {
            $this->assertArrayHasKey($pid, $byId, "target presentation $pid must appear on the page");
            $targets[$pid] = $byId[$pid];
        }

        // Warm each presentation's speakers collection (the per-presentation
        // getSpeakers() matching() cost — not the N+1 under test) and collect the
        // (presentation, speaker) pairs the serializer iterates.
        QueryTimingCollector::reset();
        $pairs = [];
        foreach ($targets as $presentation) {
            foreach ($presentation->getSpeakers() as $speaker) {
                $pairs[] = [$presentation, $speaker];
            }
        }
        $warmQueries = QueryTimingCollector::$count;
        $this->assertGreaterThanOrEqual(
            self::DISTINCT_SPEAKERS,
            count($pairs),
            'precondition: page must expose the distinct speakers'
        );

        // Measure ONLY the per-speaker accessors the preload is responsible for.
        // Members are accessed via a real field (getFirstName) to force any lazy
        // proxy to initialise — that init is exactly the N+1 the preload removes.
        QueryTimingCollector::reset();
        foreach ($pairs as [$presentation, $speaker]) {
            $member = $speaker->getMember();
            if ($member !== null) $member->getFirstName(); // force proxy init if lazy
            $speaker->getPresentationAssignmentOrder($presentation);
        }
        $accessorQueries = QueryTimingCollector::$count;

        $this->assertSame(
            0,
            $accessorQueries,
            sprintf(
                'Across %d (presentation, speaker) pairs with %d distinct speakers, preloaded member + '
                . 'assignment-order accessors must fire 0 queries; got %d (getSpeakers warm-up: %d). '
                . 'A count that scales with speakers means the /events N+1 has regressed.',
                count($pairs),
                self::DISTINCT_SPEAKERS,
                $accessorQueries,
                $warmQueries
            )
        );
    }
}
