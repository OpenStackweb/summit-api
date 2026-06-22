<?php namespace Tests;

use LaravelDoctrine\ORM\Facades\EntityManager;
use models\summit\PresentationSpeaker;
use utils\FilterParser;
use utils\Order;
use utils\OrderElement;
use utils\PagingInfo;

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

/**
 * Class SpeakerRepositoryTest
 * Regression tests for DoctrineSpeakerRepository::getAllByPage (two-phase refactor).
 */
class SpeakerRepositoryTest extends ProtectedApiTestCase
{
    use InsertSummitTestData;

    protected function setUp(): void
    {
        parent::setUp();
        self::$defaultMember = self::$member;
        self::insertSummitTestData();
    }

    protected function tearDown(): void
    {
        self::clearSummitTestData();
        parent::tearDown();
    }

    private function repo()
    {
        return EntityManager::getRepository(PresentationSpeaker::class);
    }

    // -----------------------------------------------------------------
    // getAllByPage - basic pagination
    // -----------------------------------------------------------------

    public function testGetAllByPageReturnsPagingResponse(): void
    {
        $page = $this->repo()->getAllByPage(new PagingInfo(1, 10));

        $this->assertNotNull($page);
        $this->assertGreaterThan(0, $page->getTotal());
        foreach ($page->getItems() as $speaker) {
            $this->assertInstanceOf(PresentationSpeaker::class, $speaker);
            // Entity must be hydrated enough for the serializer to call these.
            $this->assertNotNull($speaker->getId());
        }
    }

    // -----------------------------------------------------------------
    // getAllByPage - filter by first_name
    // -----------------------------------------------------------------

    public function testGetAllByPageFilterByFirstName(): void
    {
        // InsertSummitTestData seeds a speaker with first_name = "Sebastian".
        $filter = FilterParser::parse(
            ['filter' => 'first_name==Sebastian'],
            ['first_name' => ['==']]
        );

        $page = $this->repo()->getAllByPage(new PagingInfo(1, 10), $filter);

        $this->assertGreaterThan(0, $page->getTotal());
        foreach ($page->getItems() as $speaker) {
            $this->assertEquals('Sebastian', $speaker->getFirstName());
        }
    }

    // -----------------------------------------------------------------
    // getAllByPage - filter by id
    // -----------------------------------------------------------------

    public function testGetAllByPageFilterById(): void
    {
        // Get any speaker to obtain a known ID.
        $all  = $this->repo()->getAllByPage(new PagingInfo(1, 1));
        $this->assertGreaterThan(0, $all->getTotal());
        $target = $all->getItems()[0];

        $filter = FilterParser::parse(
            ['filter' => 'id==' . $target->getId()],
            ['id' => ['==']]
        );

        $page = $this->repo()->getAllByPage(new PagingInfo(1, 10), $filter);

        $this->assertEquals(1, $page->getTotal());
        $this->assertEquals($target->getId(), $page->getItems()[0]->getId());
    }

    // -----------------------------------------------------------------
    // getAllByPage - not_id filter (was silently ignored in raw-SQL version)
    // -----------------------------------------------------------------

    public function testGetAllByPageNotIdFilterExcludesSpeaker(): void
    {
        $all = $this->repo()->getAllByPage(new PagingInfo(1, 100));
        $this->assertGreaterThan(1, $all->getTotal(), 'Need at least 2 speakers for not_id test');

        $excluded = $all->getItems()[0]->getId();

        $filter = FilterParser::parse(
            ['filter' => 'not_id==' . $excluded],
            ['not_id' => ['==']]
        );

        $page = $this->repo()->getAllByPage(new PagingInfo(1, 100), $filter);

        $ids = array_map(fn($s) => $s->getId(), $page->getItems());
        $this->assertNotContains($excluded, $ids);
        $this->assertEquals($all->getTotal() - 1, $page->getTotal());
    }

    // -----------------------------------------------------------------
    // getAllByPage - order
    // -----------------------------------------------------------------

    public function testGetAllByPageOrderByFirstNameAsc(): void
    {
        $order = new Order([OrderElement::buildAscFor('first_name')]);
        $page  = $this->repo()->getAllByPage(new PagingInfo(1, 50), null, $order);

        $names = array_map(fn($s) => strtolower((string) $s->getFirstName()), $page->getItems());
        $sorted = $names;
        sort($sorted);
        $this->assertEquals($sorted, $names);
    }

    // -----------------------------------------------------------------
    // getUniqueActivitiesCountBySummit - regression for MySQL 1137
    // The previous UNION query referenced __tmp_spk_ids twice in one
    // SQL statement, triggering "Can't reopen table".
    // -----------------------------------------------------------------

    public function testGetUniqueActivitiesCountBySummitReturnsPositiveInt(): void
    {
        // InsertSummitTestData seeds speaker1 (first_name="Sebastian") on 40 presentations.
        $count = $this->repo()->getUniqueActivitiesCountBySummit(self::$summit);
        $this->assertIsInt($count);
        $this->assertGreaterThan(0, $count);
    }

    public function testGetUniqueActivitiesCountBySummitWithMatchingFilter(): void
    {
        $filter = FilterParser::parse(
            ['filter' => 'first_name==Sebastian'],
            ['first_name' => ['==']]
        );
        $unfiltered = $this->repo()->getUniqueActivitiesCountBySummit(self::$summit);
        $filtered   = $this->repo()->getUniqueActivitiesCountBySummit(self::$summit, $filter);
        // All seeded presentations use speaker1 (Sebastian), so the counts must be equal.
        $this->assertEquals($unfiltered, $filtered);
    }

    public function testGetUniqueActivitiesCountBySummitZeroForUnknownSpeaker(): void
    {
        $filter = FilterParser::parse(
            ['filter' => 'first_name==NoSuchSpeakerXYZ'],
            ['first_name' => ['==']]
        );
        $count = $this->repo()->getUniqueActivitiesCountBySummit(self::$summit, $filter);
        $this->assertEquals(0, $count);
    }

    // -----------------------------------------------------------------
    // getUniqueActivitiesCountBySummit - presentations_track_group_id
    // New filter added in this PR; zero coverage before.
    // -----------------------------------------------------------------

    public function testGetUniqueActivitiesCountBySummitFilterByPresentationsTrackGroupId(): void
    {
        // speaker1 (Sebastian) has 40 presentations in defaultTrack -> defaultTrackGroup
        $filter = FilterParser::parse(
            ['filter' => 'presentations_track_group_id==' . self::$defaultTrackGroup->getId()],
            ['presentations_track_group_id' => ['==']]
        );
        $count = $this->repo()->getUniqueActivitiesCountBySummit(self::$summit, $filter);
        $this->assertGreaterThan(0, $count);
    }

    public function testGetUniqueActivitiesCountBySummitFilterByUnknownTrackGroupIdReturnsZero(): void
    {
        $filter = FilterParser::parse(
            ['filter' => 'presentations_track_group_id==999999'],
            ['presentations_track_group_id' => ['==']]
        );
        $count = $this->repo()->getUniqueActivitiesCountBySummit(self::$summit, $filter);
        $this->assertEquals(0, $count);
    }

    // -----------------------------------------------------------------
    // getUniqueActivitiesCountBySummit - presentations_track_id
    // -----------------------------------------------------------------

    public function testGetUniqueActivitiesCountBySummitFilterByPresentationsTrackId(): void
    {
        $filter = FilterParser::parse(
            ['filter' => 'presentations_track_id==' . self::$defaultTrack->getId()],
            ['presentations_track_id' => ['==']]
        );
        $count = $this->repo()->getUniqueActivitiesCountBySummit(self::$summit, $filter);
        $this->assertGreaterThan(0, $count);
    }

    // -----------------------------------------------------------------
    // getUniqueActivitiesCountBySummit - presentations_selection_plan_id
    // -----------------------------------------------------------------

    public function testGetUniqueActivitiesCountBySummitFilterBySelectionPlanId(): void
    {
        // presentations 0-19 are in default_selection_plan; speaker1 is assigned to all of them
        $filter = FilterParser::parse(
            ['filter' => 'presentations_selection_plan_id==' . self::$default_selection_plan->getId()],
            ['presentations_selection_plan_id' => ['==']]
        );
        $count = $this->repo()->getUniqueActivitiesCountBySummit(self::$summit, $filter);
        $this->assertGreaterThan(0, $count);
    }

    // -----------------------------------------------------------------
    // getUniqueActivitiesCountBySummit - has_accepted_presentations
    // -----------------------------------------------------------------

    public function testGetUniqueActivitiesCountBySummitHasAcceptedPresentationsTrue(): void
    {
        // All seeded presentations are published (accepted); speaker1 satisfies the filter
        $filter = FilterParser::parse(
            ['filter' => 'has_accepted_presentations==true'],
            ['has_accepted_presentations' => ['==']]
        );
        $count = $this->repo()->getUniqueActivitiesCountBySummit(self::$summit, $filter);
        $this->assertGreaterThan(0, $count);
    }

    // -----------------------------------------------------------------
    // getUniqueActivitiesCountBySummit - combined filter
    // Exercises the $extraSelectionStatusFilter injection path:
    // when presentations_track_group_id is active alongside
    // has_accepted_presentations the DQL for has_accepted is rewritten
    // to also filter by track group.
    // -----------------------------------------------------------------

    public function testGetUniqueActivitiesCountBySummitHasAcceptedWithTrackGroupIdCombined(): void
    {
        // All seeded presentations are published (accepted) AND in defaultTrack (defaultTrackGroup),
        // so the combined filter matches the same set as the unfiltered query.
        $filter = FilterParser::parse(
            [
                'has_accepted_presentations==true',
                'presentations_track_group_id==' . self::$defaultTrackGroup->getId(),
            ],
            ['has_accepted_presentations' => ['=='], 'presentations_track_group_id' => ['==']]
        );

        $unfiltered = $this->repo()->getUniqueActivitiesCountBySummit(self::$summit);
        $combined   = $this->repo()->getUniqueActivitiesCountBySummit(self::$summit, $filter);

        $this->assertEquals($unfiltered, $combined);
    }

    public function testGetUniqueActivitiesCountBySummitHasAcceptedWithUnknownTrackGroupReturnsZero(): void
    {
        // The injected condition restricts has_accepted to group 999999, which has no presentations.
        // This verifies the injection actually filters (not a no-op) and produces valid DQL.
        $filter = FilterParser::parse(
            [
                'has_accepted_presentations==true',
                'presentations_track_group_id==999999',
            ],
            ['has_accepted_presentations' => ['=='], 'presentations_track_group_id' => ['==']]
        );
        $count = $this->repo()->getUniqueActivitiesCountBySummit(self::$summit, $filter);
        $this->assertEquals(0, $count);
    }
}
