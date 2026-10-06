<?php namespace Tests;

use LaravelDoctrine\ORM\Facades\EntityManager;
use models\summit\Presentation;
use models\summit\PresentationMediaUpload;
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
        $second = new PresentationSpeaker();
        $second->setFirstName('Second');
        $second->setLastName('Speaker');
        self::$em->persist($second);
        self::$em->flush();

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

    // -----------------------------------------------------------------
    // getUniqueActivitiesCountBySummit - moderator role (Phase 2 path)
    // Phase 2 has two INSERT statements: one via Presentation_Speakers
    // (speaker role) and one via Presentation.ModeratorID (moderator role).
    // All fixture speakers are added via addSpeaker(); this test is the only
    // one that exercises the moderator INSERT path.
    // -----------------------------------------------------------------

    public function testGetUniqueActivitiesCountBySummitCountsModeratorPresentation(): void
    {
        $speaker = new PresentationSpeaker();
        $speaker->setFirstName('ModeratorOnlySpeaker');
        $speaker->setLastName('TestMod');
        self::$em->persist($speaker);

        $start = new \DateTime('now', new \DateTimeZone('UTC'));
        $end   = (clone $start)->add(new \DateInterval('PT2H'));

        $p = new Presentation();
        self::$summit->addEvent($p);
        $p->setTitle('Moderator-Only Presentation');
        $p->setAbstract('Abstract');
        $p->setCategory(self::$defaultTrack);
        $p->setType(self::$defaultPresentationType);
        $p->setProgress(Presentation::PHASE_COMPLETE);
        $p->setStatus(Presentation::STATUS_RECEIVED);
        $p->setStartDate($start);
        $p->setEndDate($end);
        $p->setModerator($speaker); // moderator role only - NOT in Presentation_Speakers

        self::$em->flush();

        // Filter to this speaker exclusively to isolate the moderator path.
        $filter = FilterParser::parse(
            ['filter' => 'first_name==ModeratorOnlySpeaker'],
            ['first_name' => ['==']]
        );
        $count = $this->repo()->getUniqueActivitiesCountBySummit(self::$summit, $filter);

        // The speaker moderates exactly 1 presentation; Phase 2 must find it via ModeratorID.
        $this->assertEquals(1, $count);
    }

    // -----------------------------------------------------------------
    // getUniqueActivitiesCountBySummit - MEMORY storage engine disabled
    // Production MySQL (hardened/managed instances) disables the MEMORY
    // storage engine (error 3161, "Storage engine MEMORY is disabled").
    // disabled_storage_engines itself is a restart-only sysvar, so it
    // can't be toggled live in a test; instead we capture the emitted
    // DDL via the general query log and assert it never forces
    // ENGINE=MEMORY, which is the actual contract that breaks in prod.
    // -----------------------------------------------------------------

    public function testGetUniqueActivitiesCountBySummitDoesNotForceMemoryStorageEngine(): void
    {
        $conn = self::$em->getConnection();
        $conn->executeStatement("SET GLOBAL log_output = 'TABLE'");
        $conn->executeStatement("SET GLOBAL general_log = 'ON'");
        $conn->executeStatement("TRUNCATE TABLE mysql.general_log");
        try {
            $this->repo()->getUniqueActivitiesCountBySummit(self::$summit);

            $ddl = $conn->fetchOne(
                "SELECT argument FROM mysql.general_log
                 WHERE argument LIKE '%CREATE TEMPORARY TABLE%__tmp_spk_ids%'
                 ORDER BY event_time LIMIT 1"
            );
            $this->assertNotFalse($ddl, 'Expected the __tmp_spk_ids temp table DDL to appear in the general query log.');
            $this->assertStringNotContainsStringIgnoringCase('ENGINE=MEMORY', $ddl);
        } finally {
            $conn->executeStatement("SET GLOBAL general_log = 'OFF'");
            $conn->executeStatement("SET GLOBAL log_output = 'FILE'");
        }
    }

    // -----------------------------------------------------------------
    // getSpeakersBySummit / getUniqueActivitiesCountBySummit - has_published_presentations
    // The filter checks Presentation.published = 1 for both speaker and
    // moderator roles.
    // -----------------------------------------------------------------

    public function testGetSpeakersBySummitHasPublishedPresentationsTrueViaModeratorRole(): void
    {
        // A speaker who is the moderator (not in speakers collection) of a published
        // presentation must appear in has_published_presentations==true results.
        $moderator = new PresentationSpeaker();
        $moderator->setFirstName('PublishedModerator');
        $moderator->setLastName('TestSpeaker');
        self::$em->persist($moderator);

        $p = new Presentation();
        self::$summit->addEvent($p);
        $p->setTitle('Moderator Published Presentation');
        $p->setAbstract('Abstract');
        $p->setCategory(self::$defaultTrack);
        $p->setType(self::$defaultPresentationType);
        $p->setProgress(Presentation::PHASE_COMPLETE);
        $p->setStatus(Presentation::STATUS_RECEIVED);
        $p->setStartDate(new \DateTime('now', new \DateTimeZone('UTC')));
        $p->setEndDate((new \DateTime('now', new \DateTimeZone('UTC')))->add(new \DateInterval('PT2H')));
        $p->setModerator($moderator);
        $p->publish();
        self::$em->flush();

        $filter = FilterParser::parse(
            ['filter' => 'has_published_presentations==true'],
            ['has_published_presentations' => ['==']]
        );

        $page = $this->repo()->getSpeakersBySummit(self::$summit, new PagingInfo(1, 100), $filter);

        $ids = array_map(fn($s) => $s->getId(), $page->getItems());
        $this->assertContains($moderator->getId(), $ids);
    }

    public function testGetSpeakersBySummitHasPublishedPresentationsFalse(): void
    {
        // Create a speaker with an unpublished presentation only.
        $unpublishedSpeaker = new PresentationSpeaker();
        $unpublishedSpeaker->setFirstName('UnpublishedOnly');
        $unpublishedSpeaker->setLastName('TestSpeaker');
        self::$em->persist($unpublishedSpeaker);

        $p = new Presentation();
        self::$summit->addEvent($p);
        $p->setTitle('Unpublished Presentation');
        $p->setAbstract('Abstract');
        $p->setCategory(self::$defaultTrack);
        $p->setType(self::$defaultPresentationType);
        $p->setProgress(Presentation::PHASE_COMPLETE);
        $p->setStatus(Presentation::STATUS_RECEIVED);
        $p->setStartDate(new \DateTime('now', new \DateTimeZone('UTC')));
        $p->setEndDate((new \DateTime('now', new \DateTimeZone('UTC')))->add(new \DateInterval('PT2H')));
        $p->addSpeaker($unpublishedSpeaker);
        // Deliberately NOT calling publish() — leaves published = 0.
        self::$em->flush();

        $filter = FilterParser::parse(
            ['filter' => 'has_published_presentations==false'],
            ['has_published_presentations' => ['==']]
        );

        $page = $this->repo()->getSpeakersBySummit(self::$summit, new PagingInfo(1, 100), $filter);

        $ids = array_map(fn($s) => $s->getId(), $page->getItems());
        $this->assertContains($unpublishedSpeaker->getId(), $ids,
            'Speaker with only unpublished presentations must appear in false results');
        $this->assertNotContains(self::$defaultSpeaker->getId(), $ids,
            'Speaker with published presentations must not appear in false results');
    }

    public function testGetSpeakersBySummitHasPublishedPresentationsTrueExcludesUnpublishedOnlySpeaker(): void
    {
        // Speaker with no published presentations must be excluded from the true results.
        $unpublishedSpeaker = new PresentationSpeaker();
        $unpublishedSpeaker->setFirstName('UnpublishedOnly2');
        $unpublishedSpeaker->setLastName('TestSpeaker');
        self::$em->persist($unpublishedSpeaker);

        $p = new Presentation();
        self::$summit->addEvent($p);
        $p->setTitle('Unpublished Presentation 2');
        $p->setAbstract('Abstract');
        $p->setCategory(self::$defaultTrack);
        $p->setType(self::$defaultPresentationType);
        $p->setProgress(Presentation::PHASE_COMPLETE);
        $p->setStatus(Presentation::STATUS_RECEIVED);
        $p->setStartDate(new \DateTime('now', new \DateTimeZone('UTC')));
        $p->setEndDate((new \DateTime('now', new \DateTimeZone('UTC')))->add(new \DateInterval('PT2H')));
        $p->addSpeaker($unpublishedSpeaker);
        self::$em->flush();

        $filter = FilterParser::parse(
            ['filter' => 'has_published_presentations==true'],
            ['has_published_presentations' => ['==']]
        );

        $page = $this->repo()->getSpeakersBySummit(self::$summit, new PagingInfo(1, 100), $filter);

        $ids = array_map(fn($s) => $s->getId(), $page->getItems());
        $this->assertContains(self::$defaultSpeaker->getId(), $ids,
            'Speaker with published presentations must appear in true results');
        $this->assertNotContains($unpublishedSpeaker->getId(), $ids,
            'Speaker with only unpublished presentations must not appear in true results');
    }

    public function testGetUniqueActivitiesCountBySummitHasPublishedPresentationsTrue(): void
    {
        // All seeded presentations are published; speaker1 satisfies the filter.
        $filter = FilterParser::parse(
            ['filter' => 'has_published_presentations==true'],
            ['has_published_presentations' => ['==']]
        );
        $count = $this->repo()->getUniqueActivitiesCountBySummit(self::$summit, $filter);
        $this->assertGreaterThan(0, $count);
    }

    public function testGetUniqueActivitiesCountBySummitHasPublishedPresentationsFalseIsZeroWhenAllPublished(): void
    {
        // Every presentation in the fixture is published, so no speaker satisfies
        // has_published_presentations==false — the count of their activities must be 0.
        $filter = FilterParser::parse(
            ['filter' => 'has_published_presentations==false'],
            ['has_published_presentations' => ['==']]
        );
        $count = $this->repo()->getUniqueActivitiesCountBySummit(self::$summit, $filter);
        $this->assertEquals(0, $count);
    }

    // -----------------------------------------------------------------
    // has_published_presentations + presentations_track_id combined
    // Each condition must be satisfied by the SAME presentation.
    // The defect: a speaker with an unpublished presentation in track A
    // and a published one in track B satisfies both conditions through
    // two different presentations and is incorrectly included.
    // -----------------------------------------------------------------

    public function testHasPublishedPresentationsIsScopedByTrackFilter(): void
    {
        // Speaker satisfies each condition through a DIFFERENT presentation:
        //   - unpublished in defaultTrack   -> satisfies presentations_track_id
        //   - published   in secondaryTrack -> satisfies has_published_presentations
        // Only a mapping that scopes both into one subquery excludes them.
        $speaker = new PresentationSpeaker();
        $speaker->setFirstName('CrossPresentationMatch');
        $speaker->setLastName('TestSpeaker');
        self::$em->persist($speaker);

        $this->seedPresentation($speaker, self::$defaultTrack,   'Unpublished In Default', false);
        $this->seedPresentation($speaker, self::$secondaryTrack, 'Published In Secondary', true);

        // Positive control: published in the track being filtered.
        $control = new PresentationSpeaker();
        $control->setFirstName('PublishedInDefault');
        $control->setLastName('TestSpeaker');
        self::$em->persist($control);
        $this->seedPresentation($control, self::$defaultTrack, 'Published In Default', true);

        self::$em->flush();

        $filter = FilterParser::parse(
            [
                'has_published_presentations==true',
                'presentations_track_id==' . self::$defaultTrack->getId(),
            ],
            [
                'has_published_presentations' => ['=='],
                'presentations_track_id'      => ['=='],
            ]
        );

        $ids = array_map(
            fn($s) => $s->getId(),
            $this->repo()->getSpeakersBySummit(self::$summit, new PagingInfo(1, 100), $filter)->getItems()
        );

        $this->assertNotContains($speaker->getId(), $ids,
            'no published presentation exists in defaultTrack for this speaker');
        $this->assertContains($control->getId(), $ids,
            'speaker published in defaultTrack must still be returned');
    }

    private function seedPresentation(
        PresentationSpeaker $speaker, $track, string $title, bool $publish
    ): Presentation {
        $p = new Presentation();
        self::$summit->addEvent($p);
        $p->setTitle($title);
        $p->setAbstract('Abstract');
        $p->setCategory($track);
        $p->setType(self::$defaultPresentationType);
        $p->setProgress(Presentation::PHASE_COMPLETE);
        $p->setStatus(Presentation::STATUS_RECEIVED);
        $p->setStartDate(new \DateTime('now', new \DateTimeZone('UTC')));
        $p->setEndDate((new \DateTime('now', new \DateTimeZone('UTC')))->add(new \DateInterval('PT2H')));
        $p->addSpeaker($speaker);
        if ($publish) $p->publish();
        return $p;
    }

    public function testHasPublishedPresentationsIsScopedByTrackFilterModeratorBranch(): void
    {
        // Exercises the OR EXISTS moderator subquery specifically:
        //   - speaker: moderator of an unpublished presentation in defaultTrack
        //              AND moderator of a published presentation in secondaryTrack
        //     -> satisfies each condition through different presentations; must be excluded
        //   - control: moderator of a published presentation in defaultTrack
        //     -> must be included (validates the moderator path, not the speaker path)
        $speaker = new PresentationSpeaker();
        $speaker->setFirstName('CrossPresentationMod');
        $speaker->setLastName('TestSpeaker');
        self::$em->persist($speaker);

        // Unpublished in defaultTrack via moderator role (satisfies presentations_track_id).
        $pUnpub = new Presentation();
        self::$summit->addEvent($pUnpub);
        $pUnpub->setTitle('Mod Unpublished In Default');
        $pUnpub->setAbstract('Abstract');
        $pUnpub->setCategory(self::$defaultTrack);
        $pUnpub->setType(self::$defaultPresentationType);
        $pUnpub->setProgress(Presentation::PHASE_COMPLETE);
        $pUnpub->setStatus(Presentation::STATUS_RECEIVED);
        $pUnpub->setStartDate(new \DateTime('now', new \DateTimeZone('UTC')));
        $pUnpub->setEndDate((new \DateTime('now', new \DateTimeZone('UTC')))->add(new \DateInterval('PT2H')));
        $pUnpub->setModerator($speaker);

        // Published in secondaryTrack via moderator role (satisfies has_published_presentations).
        $p = new Presentation();
        self::$summit->addEvent($p);
        $p->setTitle('Mod Published In Secondary');
        $p->setAbstract('Abstract');
        $p->setCategory(self::$secondaryTrack);
        $p->setType(self::$defaultPresentationType);
        $p->setProgress(Presentation::PHASE_COMPLETE);
        $p->setStatus(Presentation::STATUS_RECEIVED);
        $p->setStartDate(new \DateTime('now', new \DateTimeZone('UTC')));
        $p->setEndDate((new \DateTime('now', new \DateTimeZone('UTC')))->add(new \DateInterval('PT2H')));
        $p->setModerator($speaker);
        $p->publish();

        // Positive control: moderator of a published presentation in defaultTrack.
        $control = new PresentationSpeaker();
        $control->setFirstName('ModPublishedInDefault');
        $control->setLastName('TestSpeaker');
        self::$em->persist($control);

        $pControl = new Presentation();
        self::$summit->addEvent($pControl);
        $pControl->setTitle('Mod Control Published In Default');
        $pControl->setAbstract('Abstract');
        $pControl->setCategory(self::$defaultTrack);
        $pControl->setType(self::$defaultPresentationType);
        $pControl->setProgress(Presentation::PHASE_COMPLETE);
        $pControl->setStatus(Presentation::STATUS_RECEIVED);
        $pControl->setStartDate(new \DateTime('now', new \DateTimeZone('UTC')));
        $pControl->setEndDate((new \DateTime('now', new \DateTimeZone('UTC')))->add(new \DateInterval('PT2H')));
        $pControl->setModerator($control);
        $pControl->publish();

        self::$em->flush();

        $filter = FilterParser::parse(
            [
                'has_published_presentations==true',
                'presentations_track_id==' . self::$defaultTrack->getId(),
            ],
            [
                'has_published_presentations' => ['=='],
                'presentations_track_id'      => ['=='],
            ]
        );

        $ids = array_map(
            fn($s) => $s->getId(),
            $this->repo()->getSpeakersBySummit(self::$summit, new PagingInfo(1, 100), $filter)->getItems()
        );

        $this->assertNotContains($speaker->getId(), $ids,
            'moderator with no published presentation in defaultTrack must be excluded');
        $this->assertContains($control->getId(), $ids,
            'moderator of a published presentation in defaultTrack must be returned');
    }

    // -----------------------------------------------------------------
    // getAllByPage - multi-page pagination
    // The two-phase approach uses LIMIT/OFFSET for page > 1.
    // Verifies pages are disjoint and cover the full result set.
    // -----------------------------------------------------------------

    public function testGetAllByPagePaginatesCorrectlyAcrossPages(): void
    {
        $all   = $this->repo()->getAllByPage(new PagingInfo(1, 100));
        $total = $all->getTotal();

        if ($total < 2) {
            $this->markTestSkipped('Need at least 2 speakers to test multi-page pagination.');
        }

        $perPage = (int) ceil($total / 2);

        $page1 = $this->repo()->getAllByPage(new PagingInfo(1, $perPage));
        $page2 = $this->repo()->getAllByPage(new PagingInfo(2, $perPage));

        $ids1 = array_map(fn($s) => $s->getId(), $page1->getItems());
        $ids2 = array_map(fn($s) => $s->getId(), $page2->getItems());

        $this->assertNotEmpty($ids1, 'Page 1 must not be empty');
        $this->assertNotEmpty($ids2, 'Page 2 must not be empty');
        $this->assertEmpty(array_intersect($ids1, $ids2), 'Pages must be disjoint');
        $this->assertEquals($total, $page1->getTotal(), 'Total must be consistent across pages');
        $this->assertEquals($total, $page2->getTotal());
    }
    // -----------------------------------------------------------------
    // getUniqueActivitiesCountBySummit - the count is scoped by the
    // presentation-level filters, not only by who matches them.
    //
    // Phase 1 resolves WHICH speakers match; phase 2 used to count every
    // presentation of those speakers, so any presentation-level filter
    // over-counted (a speaker with 3 presentations returned 3 for all of them).
    // -----------------------------------------------------------------

    /**
     * Seeds the acceptance scenario: one speaker owning three presentations that
     * differ in track, type, published state and media upload.
     *
     *   P1 - defaultTrack,   defaultPresentationType,    published,   media upload of type M
     *   P2 - secondaryTrack, defaultPresentationType,    published,   no media upload
     *   P3 - defaultTrack,   allow2VotePresentationType, unpublished, no media upload
     */
    private function seedActivitiesCountScenario(string $first_name): PresentationSpeaker
    {
        $speaker = new PresentationSpeaker();
        $speaker->setFirstName($first_name);
        $speaker->setLastName('ActivitiesScenario');
        self::$em->persist($speaker);

        $p1 = $this->seedPresentation($speaker, self::$defaultTrack, 'P1 Published Default Track', true);
        $media_upload = new PresentationMediaUpload();
        $media_upload->setName('P1 Media Upload');
        $media_upload->setDescription('P1 Media Upload Description');
        $media_upload->setFilename('p1.pdf');
        $media_upload->setMediaUploadType(self::$media_uploads_types[0]);
        $p1->addMediaUpload($media_upload);

        $this->seedPresentation($speaker, self::$secondaryTrack, 'P2 Published Secondary Track', true);

        // allow2VotePresentationType does not allow a publishing period, so this one
        // must not get start/end dates or SummitEvent rejects it.
        $p3 = new Presentation();
        self::$summit->addEvent($p3);
        $p3->setTitle('P3 Unpublished Default Track Other Type');
        $p3->setAbstract('Abstract');
        $p3->setCategory(self::$defaultTrack);
        $p3->setType(self::$allow2VotePresentationType);
        $p3->setProgress(Presentation::PHASE_COMPLETE);
        $p3->setStatus(Presentation::STATUS_RECEIVED);
        $p3->addSpeaker($speaker);

        self::$em->flush();

        return $speaker;
    }

    /**
     * Counts the activities of one speaker under the given presentation-level conditions.
     */
    private function countActivitiesOf(PresentationSpeaker $speaker, array $conditions = []): int
    {
        $expressions = ['id==' . $speaker->getId()];
        $rules = ['id' => ['==']];

        foreach ($conditions as $field => $value) {
            $expressions[] = $field . '==' . $value;
            $rules[$field] = ['=='];
        }

        return $this->repo()->getUniqueActivitiesCountBySummit(
            self::$summit,
            FilterParser::parse($expressions, $rules)
        );
    }

    public function testActivitiesCountWithoutPresentationFilterCountsEveryPresentationOfTheSpeaker(): void
    {
        $speaker = $this->seedActivitiesCountScenario('ScenarioAll');

        $this->assertEquals(3, $this->countActivitiesOf($speaker));
    }

    public function testActivitiesCountIsScopedByTrackFilter(): void
    {
        $speaker = $this->seedActivitiesCountScenario('ScenarioTrack');

        // P1 and P3 are in defaultTrack, P2 is in secondaryTrack.
        $this->assertEquals(2, $this->countActivitiesOf($speaker, [
            'presentations_track_id' => self::$defaultTrack->getId(),
        ]));
        $this->assertEquals(1, $this->countActivitiesOf($speaker, [
            'presentations_track_id' => self::$secondaryTrack->getId(),
        ]));
    }

    public function testActivitiesCountIsScopedByTrackGroupFilter(): void
    {
        $speaker = $this->seedActivitiesCountScenario('ScenarioTrackGroup');

        // defaultTrackGroup contains defaultTrack only: P1 and P3.
        $this->assertEquals(2, $this->countActivitiesOf($speaker, [
            'presentations_track_group_id' => self::$defaultTrackGroup->getId(),
        ]));
    }

    public function testActivitiesCountIsScopedByTypeFilter(): void
    {
        $speaker = $this->seedActivitiesCountScenario('ScenarioType');

        // only P3 uses allow2VotePresentationType
        $this->assertEquals(1, $this->countActivitiesOf($speaker, [
            'presentations_type_id' => self::$allow2VotePresentationType->getId(),
        ]));
        $this->assertEquals(2, $this->countActivitiesOf($speaker, [
            'presentations_type_id' => self::$defaultPresentationType->getId(),
        ]));
    }

    public function testActivitiesCountIsScopedByPublishedFilter(): void
    {
        $speaker = $this->seedActivitiesCountScenario('ScenarioPublished');

        // P1 and P2 are published, P3 is not.
        $this->assertEquals(2, $this->countActivitiesOf($speaker, [
            'has_published_presentations' => 'true',
        ]));
    }

    public function testActivitiesCountIsScopedByMediaUploadFilter(): void
    {
        $speaker = $this->seedActivitiesCountScenario('ScenarioMediaUpload');

        // only P1 carries a media upload of that type
        $this->assertEquals(1, $this->countActivitiesOf($speaker, [
            'has_media_upload_with_type' => self::$media_uploads_types[0]->getId(),
        ]));
    }

    public function testActivitiesCountForNotMediaUploadFilterCountsEveryPresentationOfTheMatchedSpeaker(): void
    {
        // has_not_media_upload_with_type matches a speaker only when NONE of their
        // presentations carries a media upload of that type, so every presentation
        // phase 2 reaches already qualifies.
        $speaker = new PresentationSpeaker();
        $speaker->setFirstName('ScenarioNoMediaUpload');
        $speaker->setLastName('ActivitiesScenario');
        self::$em->persist($speaker);

        $this->seedPresentation($speaker, self::$defaultTrack,   'No Media A', true);
        $this->seedPresentation($speaker, self::$secondaryTrack, 'No Media B', true);
        self::$em->flush();

        $this->assertEquals(2, $this->countActivitiesOf($speaker, [
            'has_not_media_upload_with_type' => self::$media_uploads_types[0]->getId(),
        ]));

        // the scenario speaker owns P1 with that media upload, so they do not match at all
        $with_media = $this->seedActivitiesCountScenario('ScenarioHasMediaUpload');
        $this->assertEquals(0, $this->countActivitiesOf($with_media, [
            'has_not_media_upload_with_type' => self::$media_uploads_types[0]->getId(),
        ]));
    }

    public function testActivitiesCountIsScopedByPublishedAndTrackCombined(): void
    {
        $speaker = $this->seedActivitiesCountScenario('ScenarioCombined');

        // only P1 is both published and in defaultTrack
        $this->assertEquals(1, $this->countActivitiesOf($speaker, [
            'has_published_presentations' => 'true',
            'presentations_track_id'      => self::$defaultTrack->getId(),
        ]));

        // P2 is published but in secondaryTrack
        $this->assertEquals(1, $this->countActivitiesOf($speaker, [
            'has_published_presentations' => 'true',
            'presentations_track_id'      => self::$secondaryTrack->getId(),
        ]));

        // P3 is in defaultTrack with that type but unpublished
        $this->assertEquals(0, $this->countActivitiesOf($speaker, [
            'has_published_presentations' => 'true',
            'presentations_type_id'       => self::$allow2VotePresentationType->getId(),
        ]));
    }

    public function testActivitiesCountIsScopedByTitleFilter(): void
    {
        $speaker = $this->seedActivitiesCountScenario('ScenarioTitle');

        $expressions = [
            'id==' . $speaker->getId(),
            'presentations_title=@P1 Published',
        ];
        $count = $this->repo()->getUniqueActivitiesCountBySummit(
            self::$summit,
            FilterParser::parse($expressions, ['id' => ['=='], 'presentations_title' => ['=@']])
        );

        $this->assertEquals(1, $count);
    }

    public function testActivitiesCountForPublishedFalseCountsEveryPresentationOfTheMatchedSpeaker(): void
    {
        // A speaker with no published and no selected presentation at all: the == false
        // side adds no presentation predicate, so every presentation of theirs is counted.
        $speaker = new PresentationSpeaker();
        $speaker->setFirstName('ScenarioUnpublishedOnly');
        $speaker->setLastName('ActivitiesScenario');
        self::$em->persist($speaker);

        $this->seedPresentation($speaker, self::$defaultTrack,   'Unpublished A', false);
        $this->seedPresentation($speaker, self::$secondaryTrack, 'Unpublished B', false);
        self::$em->flush();

        foreach (['has_published_presentations', 'has_accepted_presentations', 'has_alternate_presentations'] as $field) {
            $this->assertEquals(
                2,
                $this->countActivitiesOf($speaker, [$field => 'false']),
                $field . '==false must not restrict which presentations are counted'
            );
        }
    }

    public function testActivitiesCountForRejectedFalseCountsEveryPresentationOfTheMatchedSpeaker(): void
    {
        // rejected == false needs a speaker with no rejected presentation: an unpublished
        // presentation outside every selected list is rejected, so this one publishes both.
        $speaker = new PresentationSpeaker();
        $speaker->setFirstName('ScenarioNoRejected');
        $speaker->setLastName('ActivitiesScenario');
        self::$em->persist($speaker);

        $this->seedPresentation($speaker, self::$defaultTrack,   'Published A', true);
        $this->seedPresentation($speaker, self::$secondaryTrack, 'Published B', true);
        self::$em->flush();

        $this->assertEquals(2, $this->countActivitiesOf($speaker, [
            'has_rejected_presentations' => 'false',
        ]));
    }

    public function testActivitiesCountForCombinedStatusFlagsIsTheUnionOfTheStatuses(): void
    {
        // summit-admin "Accepted & Rejected": three AND'd flags. Phase 1 reads them per
        // person (one accepted AND one rejected presentation, possibly different ones),
        // so phase 2 must not demand both statuses of a single presentation.
        $speaker = new PresentationSpeaker();
        $speaker->setFirstName('ScenarioAcceptedRejected');
        $speaker->setLastName('ActivitiesScenario');
        self::$em->persist($speaker);

        $this->seedPresentation($speaker, self::$defaultTrack,   'Accepted (published)', true);
        $this->seedPresentation($speaker, self::$secondaryTrack, 'Rejected (unpublished, unlisted)', false);
        self::$em->flush();

        $this->assertEquals(2, $this->countActivitiesOf($speaker, [
            'has_rejected_presentations'  => 'true',
            'has_accepted_presentations'  => 'true',
            'has_alternate_presentations' => 'false',
        ]));
    }

    public function testActivitiesCountForOnlyAcceptedStatusDoesNotWidenPastTheTrueFlags(): void
    {
        // Guards the OR-ing of the status group from over-widening: the two == false
        // companions must stay neutral, not turn into an unrestricted OR branch.
        //
        // Can't reuse seedActivitiesCountScenario here: its P3 is unpublished and absent
        // from every list, which makes it "rejected" -- a speaker owning it never
        // satisfies phase 1's has_rejected_presentations==false (NOT EXISTS a rejected
        // presentation of theirs). Needs a speaker with zero rejected presentations, same
        // fixture shape as testActivitiesCountForRejectedFalseCountsEveryPresentationOfTheMatchedSpeaker.
        $speaker = new PresentationSpeaker();
        $speaker->setFirstName('ScenarioOnlyAccepted');
        $speaker->setLastName('ActivitiesScenario');
        self::$em->persist($speaker);

        $this->seedPresentation($speaker, self::$defaultTrack,   'Accepted A', true);
        $this->seedPresentation($speaker, self::$secondaryTrack, 'Accepted B', true);
        self::$em->flush();

        $this->assertEquals(2, $this->countActivitiesOf($speaker, [
            'has_rejected_presentations'  => 'false',
            'has_accepted_presentations'  => 'true',
            'has_alternate_presentations' => 'false',
        ]));
    }

    public function testActivitiesCountWithNoFilterIsUnaffectedByTheScoping(): void
    {
        $before = $this->repo()->getUniqueActivitiesCountBySummit(self::$summit);

        $this->seedActivitiesCountScenario('ScenarioUnfiltered');

        $after = $this->repo()->getUniqueActivitiesCountBySummit(self::$summit);

        $this->assertEquals($before + 3, $after);
    }

    public function testActivitiesCountWithAnOredPersonLevelFilterCountsEveryPresentation(): void
    {
        // "id==<speaker> OR presentations_track_id==<secondaryTrack>". Phase 1 matches the
        // speaker through either branch. Phase 2 cannot express the id branch, and an OR
        // group with a branch it cannot express must stop restricting the count rather
        // than narrow to the branches it can express -- narrowing would read as "0
        // Activities" for anyone matched only through the unmapped branch, which is what
        // summit-admin's term search does on every name match. So the whole group is
        // dropped and every presentation of the matched speaker is counted.
        $speaker = $this->seedActivitiesCountScenario('ScenarioOrGroup');

        $filter = FilterParser::parse(
            ['id==' . $speaker->getId() . ',presentations_track_id==' . self::$secondaryTrack->getId()],
            ['id' => ['=='], 'presentations_track_id' => ['==']]
        );

        $count = $this->repo()->getUniqueActivitiesCountBySummit(self::$summit, $filter);

        $this->assertEquals(3, $count);
    }

    public function testActivitiesCountForATermSearchCountsEveryPresentationOfTheMatchedSpeaker(): void
    {
        // buildTermFilter shape (summit-admin): one OR group of full_name, first_name,
        // last_name, email (all unmapped in phase 2) plus presentations_title and
        // presentations_abstract (mapped). Matched here through first_name alone, with no
        // title or abstract containing the term.
        $speaker = $this->seedActivitiesCountScenario('Zzterm');

        $count = $this->repo()->getUniqueActivitiesCountBySummit(
            self::$summit,
            FilterParser::parse(
                ['full_name=@zzterm,first_name=@zzterm,last_name=@zzterm,email=@zzterm,presentations_title=@zzterm,presentations_abstract=@zzterm'],
                [
                    'full_name'             => ['=@'],
                    'first_name'            => ['=@'],
                    'last_name'             => ['=@'],
                    'email'                 => ['=@'],
                    'presentations_title'   => ['=@'],
                    'presentations_abstract' => ['=@'],
                ]
            )
        );

        $this->assertEquals(3, $count);
    }

    public function testActivitiesCountWithAFullyMappedOrGroupStillRestricts(): void
    {
        // Guards the widening from leaking into a group phase 2 CAN fully express: every
        // branch here has a phase-2 mapping, so it must still narrow the count as before.
        $speaker = $this->seedActivitiesCountScenario('ScenarioFullyMappedOrGroup');

        $filter = FilterParser::parse(
            [
                'id==' . $speaker->getId(),
                'presentations_track_id==' . self::$secondaryTrack->getId() . ',has_published_presentations==true',
            ],
            ['id' => ['=='], 'presentations_track_id' => ['=='], 'has_published_presentations' => ['==']]
        );

        $count = $this->repo()->getUniqueActivitiesCountBySummit(self::$summit, $filter);

        // P2 matches by track, P1 and P2 match by published; P3 matches neither.
        $this->assertEquals(2, $count);
    }
}
