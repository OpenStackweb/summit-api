<?php namespace Tests;
use App\ModelSerializers\IMemberSerializerTypes;
use LaravelDoctrine\ORM\Facades\EntityManager;
use models\main\Member;
use models\summit\Presentation;
use models\summit\PresentationCategory;
use models\summit\PresentationMediaUpload;
use models\summit\PresentationSpeaker;
use ModelSerializers\SerializerRegistry;
use utils\FilterParser;
use utils\Order;
use utils\OrderElement;
use utils\PagingInfo;

/**
 * Copyright 2023 OpenStack Foundation
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
 * Class SubmitterRepositoryTest
 */
class SubmitterRepositoryTest extends ProtectedApiTestCase
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

    public function testGetSubmittersBySummit(){

        $submitter_repository = EntityManager::getRepository(Member::class);

        $filter = FilterParser::parse(
            ["filter" => "is_speaker==false"],
            ["is_speaker" => ['==']]
        );

        $order = new Order([
            OrderElement::buildDescFor("id"),
        ]);

        $page = $submitter_repository->getSubmittersBySummit(self::$summit, new PagingInfo(1, 5), $filter, $order);

        $params = [
            "summit" => self::$summit
        ];

        foreach ($page->getItems() as $submitter) {
            $sm = SerializerRegistry::getInstance()->getSerializer($submitter, IMemberSerializerTypes::Submitter)
                ->serialize('accepted_presentations,alternate_presentations,rejected_presentations', [], [], $params);
        }

        self::assertNotNull($page);
    }

    public function testGetSubmittersIdsBySummit(){
        // Seed a published presentation so member2 appears as a submitter and passes
        // has_rejected_presentations==false (rejected = unpublished + unselected;
        // a published presentation is never in that set).
        $start = new \DateTime('now', new \DateTimeZone('UTC'));
        $end   = (clone $start)->add(new \DateInterval('PT2H'));

        $p = new Presentation();
        self::$summit->addEvent($p);
        $p->setTitle('Submitter IDs Test Pres');
        $p->setAbstract('Abstract');
        $p->setCategory(self::$defaultTrack);
        $p->setType(self::$defaultPresentationType);
        $p->setProgress(Presentation::PHASE_COMPLETE);
        $p->setStatus(Presentation::STATUS_RECEIVED);
        $p->setStartDate($start);
        $p->setEndDate($end);
        $p->setCreatedBy(self::$member2);
        $p->publish();

        self::$em->flush();

        $submitter_repository = EntityManager::getRepository(Member::class);

        $filter = FilterParser::parse(
            ["filter" => "has_rejected_presentations==false"],
            ["has_rejected_presentations" => ['==']]
        );

        $order = new Order([
            OrderElement::buildDescFor("id"),
        ]);

        $submitterIds = $submitter_repository->getSubmittersIdsBySummit(self::$summit, new PagingInfo(1, 5), $filter, $order);

        self::assertNotEmpty($submitterIds);
        self::assertContains(self::$member2->getId(), $submitterIds);
    }

    public function testGetUniqueActivitiesCountBySummit(){
        // Seed 3 presentations with controlled created_by assignments so exact counts
        // can be asserted.  The trait fixture leaves created_by null on all presentations,
        // so without this seeding the method returns 0 for every call and the test is vacuous.
        //
        //   P1 + P2 - submitted by member2 (no PresentationSpeaker entity -> is_speaker==false)
        //   P3      - submitted by member, who is also a speaker on that same presentation,
        //             making them an is_speaker==true submitter.
        //
        // Re-fetch both members through the current EM.  insertSummitTestData() resets the
        // EntityManager, leaving entities from insertMemberTestData() detached.
        $member  = self::$em->find(Member::class, self::$member->getId());
        $member2 = self::$em->find(Member::class, self::$member2->getId());

        // Create a speaker for $member within this test rather than relying on
        // self::$speaker, which is detached (and may have a null ID) after the EM reset.
        $speaker = new PresentationSpeaker();
        $speaker->setFirstName('Test');
        $speaker->setLastName('Speaker');
        $speaker->setMember($member);
        self::$em->persist($speaker);

        $start = new \DateTime('now', new \DateTimeZone('UTC'));
        $end   = (clone $start)->add(new \DateInterval('PT2H'));

        foreach (['Submitter-Only Pres 1', 'Submitter-Only Pres 2'] as $title) {
            $p = new Presentation();
            self::$summit->addEvent($p);
            $p->setTitle($title);
            $p->setAbstract('Abstract');
            $p->setCategory(self::$defaultTrack);
            $p->setType(self::$defaultPresentationType);
            $p->setProgress(Presentation::PHASE_COMPLETE);
            $p->setStatus(Presentation::STATUS_RECEIVED);
            $p->setStartDate($start);
            $p->setEndDate($end);
            $p->setCreatedBy($member2);
        }

        $p3 = new Presentation();
        self::$summit->addEvent($p3);
        $p3->setTitle('Speaker-Submitter Pres');
        $p3->setAbstract('Abstract');
        $p3->setCategory(self::$defaultTrack);
        $p3->setType(self::$defaultPresentationType);
        $p3->setProgress(Presentation::PHASE_COMPLETE);
        $p3->setStatus(Presentation::STATUS_RECEIVED);
        $p3->setStartDate($start);
        $p3->setEndDate($end);
        $p3->setCreatedBy($member);
        $p3->addSpeaker($speaker); // $speaker->member = $member

        self::$em->flush();

        $submitter_repository = EntityManager::getRepository(Member::class);

        // All 3 seeded presentations have a created_by member.
        $totalCount = $submitter_repository->getUniqueActivitiesCountBySummit(self::$summit, null);
        self::assertEquals(3, $totalCount);

        // is_speaker==false: P1 and P2 only - member2 is never both creator and speaker
        // on the same presentation.  P3 is excluded because member is a speaker on their
        // own submission.
        $filter = FilterParser::parse(
            ['filter' => 'is_speaker==false'],
            ['is_speaker' => ['==']]
        );
        $filteredCount = $submitter_repository->getUniqueActivitiesCountBySummit(self::$summit, $filter);
        self::assertEquals(2, $filteredCount);
    }

    public function testGetSubmittersByTrackGroupId(): void
    {
        // P1: member2 submits in defaultTrack (belongs to defaultTrackGroup) -> must appear.
        // P2: member submits in altTrack (not in any group) -> must be excluded.
        // RED: before Task 2 the filter is silently ignored and both members are returned.
        // GREEN: after Task 2 only member2 is returned.
        $member  = self::$em->find(Member::class, self::$member->getId());
        $member2 = self::$em->find(Member::class, self::$member2->getId());

        $altTrack = new PresentationCategory();
        $altTrack->setTitle('Alt Track For Group Filter Test');
        $altTrack->setCode('ALTTST');
        $altTrack->setSessionCount(3);
        $altTrack->setAlternateCount(3);
        $altTrack->setLightningCount(3);
        $altTrack->setChairVisible(false);
        $altTrack->setVotingVisible(false);
        self::$summit->addPresentationCategory($altTrack);
        self::$em->persist($altTrack);

        $start = new \DateTime('now', new \DateTimeZone('UTC'));
        $end   = (clone $start)->add(new \DateInterval('PT2H'));

        $p1 = new Presentation();
        self::$summit->addEvent($p1);
        $p1->setTitle('Track Group Filter Test - In Group');
        $p1->setAbstract('Abstract');
        $p1->setCategory(self::$defaultTrack);
        $p1->setType(self::$defaultPresentationType);
        $p1->setProgress(Presentation::PHASE_COMPLETE);
        $p1->setStatus(Presentation::STATUS_RECEIVED);
        $p1->setStartDate($start);
        $p1->setEndDate($end);
        $p1->setCreatedBy($member2);

        $p2 = new Presentation();
        self::$summit->addEvent($p2);
        $p2->setTitle('Track Group Filter Test - Not In Group');
        $p2->setAbstract('Abstract');
        $p2->setCategory($altTrack);
        $p2->setType(self::$defaultPresentationType);
        $p2->setProgress(Presentation::PHASE_COMPLETE);
        $p2->setStatus(Presentation::STATUS_RECEIVED);
        $p2->setStartDate($start);
        $p2->setEndDate($end);
        $p2->setCreatedBy($member);

        self::$em->flush();

        $submitter_repository = EntityManager::getRepository(Member::class);

        $filter = FilterParser::parse(
            ["filter" => sprintf("presentations_track_group_id==%s", self::$defaultTrackGroup->getId())],
            ["presentations_track_group_id" => ['==']]
        );

        $page = $submitter_repository->getSubmittersBySummit(self::$summit, new PagingInfo(1, 10), $filter, null);

        self::assertNotNull($page);
        $ids = array_map(fn($m) => $m->getId(), $page->getItems());
        self::assertContains($member2->getId(), $ids, 'member2 (defaultTrack in defaultTrackGroup) must be included');
        self::assertNotContains($member->getId(), $ids, 'member (altTrack, no group) must be excluded');
    }

    // -----------------------------------------------------------------
    // presentations_track_id filter
    // -----------------------------------------------------------------

    public function testGetSubmittersByPresentationsTrackId(): void
    {
        $member  = self::$em->find(Member::class, self::$member->getId());
        $member2 = self::$em->find(Member::class, self::$member2->getId());

        $altTrack = new PresentationCategory();
        $altTrack->setTitle('Alt Track For Track ID Filter Test');
        $altTrack->setCode('ALTTID');
        $altTrack->setSessionCount(3);
        $altTrack->setAlternateCount(3);
        $altTrack->setLightningCount(3);
        $altTrack->setChairVisible(false);
        $altTrack->setVotingVisible(false);
        self::$summit->addPresentationCategory($altTrack);
        self::$em->persist($altTrack);

        $start = new \DateTime('now', new \DateTimeZone('UTC'));
        $end   = (clone $start)->add(new \DateInterval('PT2H'));

        $p1 = new Presentation();
        self::$summit->addEvent($p1);
        $p1->setTitle('Track ID Filter - Default Track');
        $p1->setAbstract('Abstract');
        $p1->setCategory(self::$defaultTrack);
        $p1->setType(self::$defaultPresentationType);
        $p1->setProgress(Presentation::PHASE_COMPLETE);
        $p1->setStatus(Presentation::STATUS_RECEIVED);
        $p1->setStartDate($start);
        $p1->setEndDate($end);
        $p1->setCreatedBy($member2);

        $p2 = new Presentation();
        self::$summit->addEvent($p2);
        $p2->setTitle('Track ID Filter - Alt Track');
        $p2->setAbstract('Abstract');
        $p2->setCategory($altTrack);
        $p2->setType(self::$defaultPresentationType);
        $p2->setProgress(Presentation::PHASE_COMPLETE);
        $p2->setStatus(Presentation::STATUS_RECEIVED);
        $p2->setStartDate($start);
        $p2->setEndDate($end);
        $p2->setCreatedBy($member);

        self::$em->flush();

        $repo = EntityManager::getRepository(Member::class);
        $filter = FilterParser::parse(
            ['filter' => 'presentations_track_id==' . self::$defaultTrack->getId()],
            ['presentations_track_id' => ['==']]
        );
        $page = $repo->getSubmittersBySummit(self::$summit, new PagingInfo(1, 10), $filter, null);

        $ids = array_map(fn($m) => $m->getId(), $page->getItems());
        self::assertContains($member2->getId(), $ids, 'member2 (defaultTrack) must be included');
        self::assertNotContains($member->getId(), $ids, 'member (altTrack) must be excluded');
    }

    // -----------------------------------------------------------------
    // presentations_selection_plan_id filter
    // -----------------------------------------------------------------

    public function testGetSubmittersBySelectionPlanId(): void
    {
        $member  = self::$em->find(Member::class, self::$member->getId());
        $member2 = self::$em->find(Member::class, self::$member2->getId());

        $start = new \DateTime('now', new \DateTimeZone('UTC'));
        $end   = (clone $start)->add(new \DateInterval('PT2H'));

        // p1: member2 in default_selection_plan
        $p1 = new Presentation();
        self::$summit->addEvent($p1);
        $p1->setTitle('Selection Plan Filter - In Plan');
        $p1->setAbstract('Abstract');
        $p1->setCategory(self::$defaultTrack);
        $p1->setType(self::$defaultPresentationType);
        $p1->setProgress(Presentation::PHASE_COMPLETE);
        $p1->setStatus(Presentation::STATUS_RECEIVED);
        $p1->setStartDate($start);
        $p1->setEndDate($end);
        $p1->setCreatedBy($member2);
        self::$default_selection_plan->addPresentation($p1);

        // p2: member not in any selection plan
        $p2 = new Presentation();
        self::$summit->addEvent($p2);
        $p2->setTitle('Selection Plan Filter - No Plan');
        $p2->setAbstract('Abstract');
        $p2->setCategory(self::$defaultTrack);
        $p2->setType(self::$defaultPresentationType);
        $p2->setProgress(Presentation::PHASE_COMPLETE);
        $p2->setStatus(Presentation::STATUS_RECEIVED);
        $p2->setStartDate($start);
        $p2->setEndDate($end);
        $p2->setCreatedBy($member);

        self::$em->flush();

        $repo = EntityManager::getRepository(Member::class);
        $filter = FilterParser::parse(
            ['filter' => 'presentations_selection_plan_id==' . self::$default_selection_plan->getId()],
            ['presentations_selection_plan_id' => ['==']]
        );
        $page = $repo->getSubmittersBySummit(self::$summit, new PagingInfo(1, 10), $filter, null);

        $ids = array_map(fn($m) => $m->getId(), $page->getItems());
        self::assertContains($member2->getId(), $ids, 'member2 (in selection plan) must be included');
        self::assertNotContains($member->getId(), $ids, 'member (no selection plan) must be excluded');
    }

    // -----------------------------------------------------------------
    // has_accepted_presentations filter
    // -----------------------------------------------------------------

    public function testGetSubmittersHasAcceptedPresentationsTrue(): void
    {
        $member  = self::$em->find(Member::class, self::$member->getId());
        $member2 = self::$em->find(Member::class, self::$member2->getId());

        $start = new \DateTime('now', new \DateTimeZone('UTC'));
        $end   = (clone $start)->add(new \DateInterval('PT2H'));

        // member2: published (accepted) presentation
        $p1 = new Presentation();
        self::$summit->addEvent($p1);
        $p1->setTitle('Accepted Filter - Published');
        $p1->setAbstract('Abstract');
        $p1->setCategory(self::$defaultTrack);
        $p1->setType(self::$defaultPresentationType);
        $p1->setProgress(Presentation::PHASE_COMPLETE);
        $p1->setStatus(Presentation::STATUS_RECEIVED);
        $p1->setStartDate($start);
        $p1->setEndDate($end);
        $p1->setCreatedBy($member2);
        $p1->publish();

        // member: unpublished (not accepted)
        $p2 = new Presentation();
        self::$summit->addEvent($p2);
        $p2->setTitle('Accepted Filter - Unpublished');
        $p2->setAbstract('Abstract');
        $p2->setCategory(self::$defaultTrack);
        $p2->setType(self::$defaultPresentationType);
        $p2->setProgress(Presentation::PHASE_COMPLETE);
        $p2->setStatus(Presentation::STATUS_RECEIVED);
        $p2->setStartDate($start);
        $p2->setEndDate($end);
        $p2->setCreatedBy($member);

        self::$em->flush();

        $repo = EntityManager::getRepository(Member::class);
        $filter = FilterParser::parse(
            ['filter' => 'has_accepted_presentations==true'],
            ['has_accepted_presentations' => ['==']]
        );
        $page = $repo->getSubmittersBySummit(self::$summit, new PagingInfo(1, 10), $filter, null);

        $ids = array_map(fn($m) => $m->getId(), $page->getItems());
        self::assertContains($member2->getId(), $ids, 'member2 (published presentation) must be included');
        self::assertNotContains($member->getId(), $ids, 'member (unpublished presentation) must be excluded');
    }

    // -----------------------------------------------------------------
    // combined has_accepted_presentations + presentations_track_id
    // Exercises the $extraSelectionStatusFilter injection path: when
    // presentations_track_id is active alongside has_accepted_presentations
    // the accepted-check DQL is rewritten to also filter by track.
    // -----------------------------------------------------------------

    public function testGetSubmittersHasAcceptedWithTrackIdCombined(): void
    {
        $member2 = self::$em->find(Member::class, self::$member2->getId());

        $start = new \DateTime('now', new \DateTimeZone('UTC'));
        $end   = (clone $start)->add(new \DateInterval('PT2H'));

        $p = new Presentation();
        self::$summit->addEvent($p);
        $p->setTitle('Combined Filter - Accepted + Track');
        $p->setAbstract('Abstract');
        $p->setCategory(self::$defaultTrack);
        $p->setType(self::$defaultPresentationType);
        $p->setProgress(Presentation::PHASE_COMPLETE);
        $p->setStatus(Presentation::STATUS_RECEIVED);
        $p->setStartDate($start);
        $p->setEndDate($end);
        $p->setCreatedBy($member2);
        $p->publish();

        self::$em->flush();

        $repo = EntityManager::getRepository(Member::class);
        $filter = FilterParser::parse(
            [
                'has_accepted_presentations==true',
                'presentations_track_id==' . self::$defaultTrack->getId(),
            ],
            ['has_accepted_presentations' => ['=='], 'presentations_track_id' => ['==']]
        );
        $page = $repo->getSubmittersBySummit(self::$summit, new PagingInfo(1, 10), $filter, null);

        $ids = array_map(fn($m) => $m->getId(), $page->getItems());
        self::assertContains($member2->getId(), $ids,
            'member2 must appear: published presentation in defaultTrack');
    }

    // -----------------------------------------------------------------
    // has_published_presentations filter
    // Submitters are identified by created_by.
    // -----------------------------------------------------------------

    public function testGetSubmittersHasPublishedPresentationsTrue(): void
    {
        $member  = self::$em->find(Member::class, self::$member->getId());
        $member2 = self::$em->find(Member::class, self::$member2->getId());

        $start = new \DateTime('now', new \DateTimeZone('UTC'));
        $end   = (clone $start)->add(new \DateInterval('PT2H'));

        // member2: published presentation
        $p1 = new Presentation();
        self::$summit->addEvent($p1);
        $p1->setTitle('Published Filter - Published');
        $p1->setAbstract('Abstract');
        $p1->setCategory(self::$defaultTrack);
        $p1->setType(self::$defaultPresentationType);
        $p1->setProgress(Presentation::PHASE_COMPLETE);
        $p1->setStatus(Presentation::STATUS_RECEIVED);
        $p1->setStartDate($start);
        $p1->setEndDate($end);
        $p1->setCreatedBy($member2);
        $p1->publish();

        // member: unpublished presentation only
        $p2 = new Presentation();
        self::$summit->addEvent($p2);
        $p2->setTitle('Published Filter - Unpublished');
        $p2->setAbstract('Abstract');
        $p2->setCategory(self::$defaultTrack);
        $p2->setType(self::$defaultPresentationType);
        $p2->setProgress(Presentation::PHASE_COMPLETE);
        $p2->setStatus(Presentation::STATUS_RECEIVED);
        $p2->setStartDate($start);
        $p2->setEndDate($end);
        $p2->setCreatedBy($member);
        // Deliberately NOT calling publish()

        self::$em->flush();

        $repo   = EntityManager::getRepository(Member::class);
        $filter = FilterParser::parse(
            ['filter' => 'has_published_presentations==true'],
            ['has_published_presentations' => ['==']]
        );
        $page = $repo->getSubmittersBySummit(self::$summit, new PagingInfo(1, 10), $filter, null);

        $ids = array_map(fn($m) => $m->getId(), $page->getItems());
        self::assertContains($member2->getId(), $ids,
            'member2 (published presentation) must be included');
        self::assertNotContains($member->getId(), $ids,
            'member (unpublished presentation only) must be excluded');
    }

    public function testGetSubmittersHasPublishedPresentationsFalse(): void
    {
        $member  = self::$em->find(Member::class, self::$member->getId());
        $member2 = self::$em->find(Member::class, self::$member2->getId());

        $start = new \DateTime('now', new \DateTimeZone('UTC'));
        $end   = (clone $start)->add(new \DateInterval('PT2H'));

        // member: unpublished presentation only
        $p1 = new Presentation();
        self::$summit->addEvent($p1);
        $p1->setTitle('Published False Filter - Unpublished');
        $p1->setAbstract('Abstract');
        $p1->setCategory(self::$defaultTrack);
        $p1->setType(self::$defaultPresentationType);
        $p1->setProgress(Presentation::PHASE_COMPLETE);
        $p1->setStatus(Presentation::STATUS_RECEIVED);
        $p1->setStartDate($start);
        $p1->setEndDate($end);
        $p1->setCreatedBy($member);

        // member2: published presentation
        $p2 = new Presentation();
        self::$summit->addEvent($p2);
        $p2->setTitle('Published False Filter - Published');
        $p2->setAbstract('Abstract');
        $p2->setCategory(self::$defaultTrack);
        $p2->setType(self::$defaultPresentationType);
        $p2->setProgress(Presentation::PHASE_COMPLETE);
        $p2->setStatus(Presentation::STATUS_RECEIVED);
        $p2->setStartDate($start);
        $p2->setEndDate($end);
        $p2->setCreatedBy($member2);
        $p2->publish();

        self::$em->flush();

        $repo   = EntityManager::getRepository(Member::class);
        $filter = FilterParser::parse(
            ['filter' => 'has_published_presentations==false'],
            ['has_published_presentations' => ['==']]
        );
        $page = $repo->getSubmittersBySummit(self::$summit, new PagingInfo(1, 10), $filter, null);

        $ids = array_map(fn($m) => $m->getId(), $page->getItems());
        self::assertContains($member->getId(), $ids,
            'member (unpublished presentation only) must be included');
        self::assertNotContains($member2->getId(), $ids,
            'member2 (published presentation) must be excluded');
    }

    public function testGetUniqueActivitiesCountBySummitHasPublishedPresentationsTrue(): void
    {
        $member2 = self::$em->find(Member::class, self::$member2->getId());

        $start = new \DateTime('now', new \DateTimeZone('UTC'));
        $end   = (clone $start)->add(new \DateInterval('PT2H'));

        $p = new Presentation();
        self::$summit->addEvent($p);
        $p->setTitle('Count Published - Published');
        $p->setAbstract('Abstract');
        $p->setCategory(self::$defaultTrack);
        $p->setType(self::$defaultPresentationType);
        $p->setProgress(Presentation::PHASE_COMPLETE);
        $p->setStatus(Presentation::STATUS_RECEIVED);
        $p->setStartDate($start);
        $p->setEndDate($end);
        $p->setCreatedBy($member2);
        $p->publish();
        self::$em->flush();

        $repo   = EntityManager::getRepository(Member::class);
        $filter = FilterParser::parse(
            ['filter' => 'has_published_presentations==true'],
            ['has_published_presentations' => ['==']]
        );
        $count = $repo->getUniqueActivitiesCountBySummit(self::$summit, $filter);
        $this->assertGreaterThan(0, $count);
    }

    public function testGetUniqueActivitiesCountBySummitHasPublishedPresentationsFalseIsZeroWhenAllSubmittersHavePublished(): void
    {
        $member2 = self::$em->find(Member::class, self::$member2->getId());

        $start = new \DateTime('now', new \DateTimeZone('UTC'));
        $end   = (clone $start)->add(new \DateInterval('PT2H'));

        // Only a published presentation exists for member2 in this summit.
        $p = new Presentation();
        self::$summit->addEvent($p);
        $p->setTitle('Count Published False - Published Only');
        $p->setAbstract('Abstract');
        $p->setCategory(self::$defaultTrack);
        $p->setType(self::$defaultPresentationType);
        $p->setProgress(Presentation::PHASE_COMPLETE);
        $p->setStatus(Presentation::STATUS_RECEIVED);
        $p->setStartDate($start);
        $p->setEndDate($end);
        $p->setCreatedBy($member2);
        $p->publish();
        self::$em->flush();

        $repo   = EntityManager::getRepository(Member::class);
        $filter = FilterParser::parse(
            ['filter' => 'has_published_presentations==false'],
            ['has_published_presentations' => ['==']]
        );
        // member2 has a published presentation so they don't satisfy false;
        // no submitter in this summit satisfies the filter → count must be 0.
        $count = $repo->getUniqueActivitiesCountBySummit(self::$summit, $filter);
        $this->assertEquals(0, $count);
    }

    // -----------------------------------------------------------------
    // has_published_presentations + presentations_track_id combined
    // Each condition must be satisfied by the SAME presentation.
    // The defect: a submitter with an unpublished presentation in track A
    // and a published one in track B satisfies both conditions through
    // two different presentations and is incorrectly included.
    // -----------------------------------------------------------------

    public function testHasPublishedPresentationsIsScopedByTrackFilter(): void
    {
        $member  = self::$em->find(Member::class, self::$member->getId());
        $member2 = self::$em->find(Member::class, self::$member2->getId());

        // member satisfies each condition through a DIFFERENT presentation.
        $this->seedPresentation($member,  self::$defaultTrack,   'Unpublished In Default', false);
        $this->seedPresentation($member,  self::$secondaryTrack, 'Published In Secondary', true);

        // Positive control: published in the track being filtered.
        $this->seedPresentation($member2, self::$defaultTrack,   'Published In Default',   true);

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

        $repo = EntityManager::getRepository(Member::class);
        $ids  = array_map(
            fn($m) => $m->getId(),
            $repo->getSubmittersBySummit(self::$summit, new PagingInfo(1, 10), $filter, null)->getItems()
        );

        self::assertNotContains($member->getId(), $ids,
            'member has no published presentation in defaultTrack');
        self::assertContains($member2->getId(), $ids,
            'member2 published in defaultTrack must still be returned');
    }

    private function seedPresentation(Member $creator, $track, string $title, bool $publish): Presentation
    {
        $start = new \DateTime('now', new \DateTimeZone('UTC'));

        $p = new Presentation();
        self::$summit->addEvent($p);
        $p->setTitle($title);
        $p->setAbstract('Abstract');
        $p->setCategory($track);
        $p->setType(self::$defaultPresentationType);
        $p->setProgress(Presentation::PHASE_COMPLETE);
        $p->setStatus(Presentation::STATUS_RECEIVED);
        $p->setStartDate($start);
        $p->setEndDate((clone $start)->add(new \DateInterval('PT2H')));
        $p->setCreatedBy($creator);
        if ($publish) $p->publish();
        return $p;
    }

    // -----------------------------------------------------------------
    // getUniqueActivitiesCountBySummit - presentations_track_group_id
    // The submitter repo and speaker repo share the filter name but use
    // different DQL paths. The speaker repo has this covered; the
    // submitter path (buildSubmitterBaseQuery + __tmp_mbr_ids) does not.
    // -----------------------------------------------------------------

    public function testGetUniqueActivitiesCountBySummitFilterByPresentationsTrackGroupId(): void
    {
        $member2 = self::$em->find(Member::class, self::$member2->getId());

        $start = new \DateTime('now', new \DateTimeZone('UTC'));
        $end   = (clone $start)->add(new \DateInterval('PT2H'));

        // P1: member2 submits in defaultTrack, which belongs to defaultTrackGroup.
        $p1 = new Presentation();
        self::$summit->addEvent($p1);
        $p1->setTitle('Submitter Count - In Track Group');
        $p1->setAbstract('Abstract');
        $p1->setCategory(self::$defaultTrack);
        $p1->setType(self::$defaultPresentationType);
        $p1->setProgress(Presentation::PHASE_COMPLETE);
        $p1->setStatus(Presentation::STATUS_RECEIVED);
        $p1->setStartDate($start);
        $p1->setEndDate($end);
        $p1->setCreatedBy($member2);

        self::$em->flush();

        $repo = EntityManager::getRepository(Member::class);

        $filter = FilterParser::parse(
            ['filter' => 'presentations_track_group_id==' . self::$defaultTrackGroup->getId()],
            ['presentations_track_group_id' => ['==']]
        );
        $count = $repo->getUniqueActivitiesCountBySummit(self::$summit, $filter);
        $this->assertGreaterThan(0, $count, 'Must count presentations by submitters in defaultTrackGroup');

        $filterNone = FilterParser::parse(
            ['filter' => 'presentations_track_group_id==999999'],
            ['presentations_track_group_id' => ['==']]
        );
        $countNone = $repo->getUniqueActivitiesCountBySummit(self::$summit, $filterNone);
        $this->assertEquals(0, $countNone, 'Non-existent track group must yield 0');
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
            $repo = EntityManager::getRepository(Member::class);
            $repo->getUniqueActivitiesCountBySummit(self::$summit);

            $ddl = $conn->fetchOne(
                "SELECT argument FROM mysql.general_log
                 WHERE argument LIKE '%CREATE TEMPORARY TABLE%__tmp_mbr_ids%'
                 ORDER BY event_time LIMIT 1"
            );
            $this->assertNotFalse($ddl, 'Expected the __tmp_mbr_ids temp table DDL to appear in the general query log.');
            $this->assertStringNotContainsStringIgnoringCase('ENGINE=MEMORY', $ddl);
        } finally {
            $conn->executeStatement("SET GLOBAL general_log = 'OFF'");
            $conn->executeStatement("SET GLOBAL log_output = 'FILE'");
        }
    }

    // -----------------------------------------------------------------
    // getSubmittersBySummit - multi-page pagination
    // The two-phase refactor uses LIMIT/OFFSET for page > 1.
    // Verifies page 2 is non-empty, disjoint from page 1, and reports
    // the same total as page 1.
    // -----------------------------------------------------------------

    public function testGetSubmittersBySummitPaginatesCorrectlyAcrossPages(): void
    {
        $member  = self::$em->find(Member::class, self::$member->getId());
        $member2 = self::$em->find(Member::class, self::$member2->getId());

        $start = new \DateTime('now', new \DateTimeZone('UTC'));
        $end   = (clone $start)->add(new \DateInterval('PT2H'));

        foreach ([$member, $member2] as $creator) {
            $p = new Presentation();
            self::$summit->addEvent($p);
            $p->setTitle('Pagination Test Submission - ' . $creator->getId());
            $p->setAbstract('Abstract');
            $p->setCategory(self::$defaultTrack);
            $p->setType(self::$defaultPresentationType);
            $p->setProgress(Presentation::PHASE_COMPLETE);
            $p->setStatus(Presentation::STATUS_RECEIVED);
            $p->setStartDate($start);
            $p->setEndDate($end);
            $p->setCreatedBy($creator);
        }
        self::$em->flush();

        $repo = EntityManager::getRepository(Member::class);

        $page1 = $repo->getSubmittersBySummit(self::$summit, new PagingInfo(1, 1), null, null);
        $page2 = $repo->getSubmittersBySummit(self::$summit, new PagingInfo(2, 1), null, null);

        $ids1 = array_map(fn($m) => $m->getId(), $page1->getItems());
        $ids2 = array_map(fn($m) => $m->getId(), $page2->getItems());

        $this->assertNotEmpty($ids1, 'Page 1 must not be empty');
        $this->assertNotEmpty($ids2, 'Page 2 must not be empty');
        $this->assertEmpty(array_intersect($ids1, $ids2), 'Page 1 and page 2 must not share submitters');
        $this->assertEquals($page1->getTotal(), $page2->getTotal(), 'Total must be consistent across pages');
    }

    // -----------------------------------------------------------------
    // getSubmittersBySummit - empty result early-exit path
    // When no IDs match, the method returns early without a Phase-3 query.
    // -----------------------------------------------------------------

    public function testGetSubmittersBySummitReturnsEmptyPageForNonMatchingFilter(): void
    {
        $filter = FilterParser::parse(
            ['filter' => 'first_name==__NONEXISTENT_9999__'],
            ['first_name' => ['==']]
        );

        $repo = EntityManager::getRepository(Member::class);
        $page = $repo->getSubmittersBySummit(self::$summit, new PagingInfo(1, 10), $filter, null);

        $this->assertNotNull($page);
        $this->assertEquals(0, $page->getTotal());
        $this->assertEmpty($page->getItems());
    }
    // -----------------------------------------------------------------
    // getUniqueActivitiesCountBySummit - the count is scoped by the
    // presentation-level filters, not only by who matches them.
    //
    // Phase 1 resolves WHICH submitters match; phase 2 used to count every
    // presentation of those submitters, so any presentation-level filter
    // over-counted (a submitter with 3 presentations returned 3 for all of them).
    // -----------------------------------------------------------------

    /**
     * Seeds the acceptance scenario: one submitter owning three presentations that
     * differ in track, type, published state and media upload. InsertSummitTestData
     * never sets created_by, so these are the only presentations of this member.
     *
     *   P1 - defaultTrack,   defaultPresentationType,    published,   media upload of type M
     *   P2 - secondaryTrack, defaultPresentationType,    published,   no media upload
     *   P3 - defaultTrack,   allow2VotePresentationType, unpublished, no media upload
     */
    private function seedActivitiesCountScenario(): Member
    {
        $submitter = self::$em->find(Member::class, self::$member2->getId());

        $p1 = $this->seedPresentation($submitter, self::$defaultTrack, 'P1 Published Default Track', true);
        $media_upload = new PresentationMediaUpload();
        $media_upload->setName('P1 Media Upload');
        $media_upload->setDescription('P1 Media Upload Description');
        $media_upload->setFilename('p1.pdf');
        $media_upload->setMediaUploadType(self::$media_uploads_types[0]);
        $p1->addMediaUpload($media_upload);

        $this->seedPresentation($submitter, self::$secondaryTrack, 'P2 Published Secondary Track', true);

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
        $p3->setCreatedBy($submitter);

        self::$em->flush();

        return $submitter;
    }

    /**
     * Counts the activities of one submitter under the given presentation-level conditions.
     */
    private function countActivitiesOf(Member $submitter, array $conditions = []): int
    {
        $expressions = ['id==' . $submitter->getId()];
        $rules = ['id' => ['==']];

        foreach ($conditions as $field => $value) {
            $expressions[] = $field . '==' . $value;
            $rules[$field] = ['=='];
        }

        return EntityManager::getRepository(Member::class)->getUniqueActivitiesCountBySummit(
            self::$summit,
            FilterParser::parse($expressions, $rules)
        );
    }

    public function testActivitiesCountWithoutPresentationFilterCountsEveryPresentationOfTheSubmitter(): void
    {
        $submitter = $this->seedActivitiesCountScenario();

        $this->assertEquals(3, $this->countActivitiesOf($submitter));
    }

    public function testActivitiesCountIsScopedByTrackFilter(): void
    {
        $submitter = $this->seedActivitiesCountScenario();

        // P1 and P3 are in defaultTrack, P2 is in secondaryTrack.
        $this->assertEquals(2, $this->countActivitiesOf($submitter, [
            'presentations_track_id' => self::$defaultTrack->getId(),
        ]));
        $this->assertEquals(1, $this->countActivitiesOf($submitter, [
            'presentations_track_id' => self::$secondaryTrack->getId(),
        ]));
    }

    public function testActivitiesCountIsScopedByTrackGroupFilter(): void
    {
        $submitter = $this->seedActivitiesCountScenario();

        // defaultTrackGroup contains defaultTrack only: P1 and P3.
        $this->assertEquals(2, $this->countActivitiesOf($submitter, [
            'presentations_track_group_id' => self::$defaultTrackGroup->getId(),
        ]));
    }

    public function testActivitiesCountIsScopedByTypeFilter(): void
    {
        $submitter = $this->seedActivitiesCountScenario();

        // only P3 uses allow2VotePresentationType
        $this->assertEquals(1, $this->countActivitiesOf($submitter, [
            'presentations_type_id' => self::$allow2VotePresentationType->getId(),
        ]));
        $this->assertEquals(2, $this->countActivitiesOf($submitter, [
            'presentations_type_id' => self::$defaultPresentationType->getId(),
        ]));
    }

    public function testActivitiesCountIsScopedByPublishedFilter(): void
    {
        $submitter = $this->seedActivitiesCountScenario();

        // P1 and P2 are published, P3 is not.
        $this->assertEquals(2, $this->countActivitiesOf($submitter, [
            'has_published_presentations' => 'true',
        ]));
    }

    public function testActivitiesCountIsScopedByMediaUploadFilter(): void
    {
        $submitter = $this->seedActivitiesCountScenario();

        // only P1 carries a media upload of that type
        $this->assertEquals(1, $this->countActivitiesOf($submitter, [
            'has_media_upload_with_type' => self::$media_uploads_types[0]->getId(),
        ]));
    }

    public function testActivitiesCountForNotMediaUploadFilterCountsEveryPresentationOfTheMatchedSubmitter(): void
    {
        // has_not_media_upload_with_type matches a submitter only when NONE of their
        // presentations carries a media upload of that type, so every presentation
        // phase 2 reaches already qualifies.
        $submitter = self::$em->find(Member::class, self::$member2->getId());

        $this->seedPresentation($submitter, self::$defaultTrack,   'No Media A', true);
        $this->seedPresentation($submitter, self::$secondaryTrack, 'No Media B', true);
        self::$em->flush();

        $this->assertEquals(2, $this->countActivitiesOf($submitter, [
            'has_not_media_upload_with_type' => self::$media_uploads_types[0]->getId(),
        ]));
    }

    public function testActivitiesCountIsZeroWhenTheSubmitterOwnsAMediaUploadOfThatType(): void
    {
        // the scenario submitter owns P1 with that media upload, so they do not match at all
        $submitter = $this->seedActivitiesCountScenario();

        $this->assertEquals(0, $this->countActivitiesOf($submitter, [
            'has_not_media_upload_with_type' => self::$media_uploads_types[0]->getId(),
        ]));
    }

    public function testActivitiesCountIsScopedByPublishedAndTrackCombined(): void
    {
        $submitter = $this->seedActivitiesCountScenario();

        // only P1 is both published and in defaultTrack
        $this->assertEquals(1, $this->countActivitiesOf($submitter, [
            'has_published_presentations' => 'true',
            'presentations_track_id'      => self::$defaultTrack->getId(),
        ]));

        // P2 is published but in secondaryTrack
        $this->assertEquals(1, $this->countActivitiesOf($submitter, [
            'has_published_presentations' => 'true',
            'presentations_track_id'      => self::$secondaryTrack->getId(),
        ]));

        // P3 is in defaultTrack with that type but unpublished
        $this->assertEquals(0, $this->countActivitiesOf($submitter, [
            'has_published_presentations' => 'true',
            'presentations_type_id'       => self::$allow2VotePresentationType->getId(),
        ]));
    }

    public function testActivitiesCountIsScopedByTitleFilter(): void
    {
        $submitter = $this->seedActivitiesCountScenario();

        $count = EntityManager::getRepository(Member::class)->getUniqueActivitiesCountBySummit(
            self::$summit,
            FilterParser::parse(
                ['id==' . $submitter->getId(), 'presentations_title=@P1 Published'],
                ['id' => ['=='], 'presentations_title' => ['=@']]
            )
        );

        $this->assertEquals(1, $count);
    }

    public function testActivitiesCountForPublishedFalseCountsEveryPresentationOfTheMatchedSubmitter(): void
    {
        // A submitter with no published and no selected presentation at all: the == false
        // side adds no presentation predicate, so every presentation of theirs is counted.
        $submitter = self::$em->find(Member::class, self::$member2->getId());

        $this->seedPresentation($submitter, self::$defaultTrack,   'Unpublished A', false);
        $this->seedPresentation($submitter, self::$secondaryTrack, 'Unpublished B', false);
        self::$em->flush();

        foreach (['has_published_presentations', 'has_accepted_presentations', 'has_alternate_presentations'] as $field) {
            $this->assertEquals(
                2,
                $this->countActivitiesOf($submitter, [$field => 'false']),
                $field . '==false must not restrict which presentations are counted'
            );
        }
    }

    public function testActivitiesCountForRejectedFalseCountsEveryPresentationOfTheMatchedSubmitter(): void
    {
        // rejected == false needs a submitter with no rejected presentation: an unpublished
        // presentation outside every selected list is rejected, so this one publishes both.
        $submitter = self::$em->find(Member::class, self::$member2->getId());

        $this->seedPresentation($submitter, self::$defaultTrack,   'Published A', true);
        $this->seedPresentation($submitter, self::$secondaryTrack, 'Published B', true);
        self::$em->flush();

        $this->assertEquals(2, $this->countActivitiesOf($submitter, [
            'has_rejected_presentations' => 'false',
        ]));
    }

    public function testActivitiesCountWithNoFilterIsUnaffectedByTheScoping(): void
    {
        $repo   = EntityManager::getRepository(Member::class);
        $before = $repo->getUniqueActivitiesCountBySummit(self::$summit);

        $this->seedActivitiesCountScenario();

        $this->assertEquals($before + 3, $repo->getUniqueActivitiesCountBySummit(self::$summit));
    }

    public function testActivitiesCountWithAnOredPersonLevelFilterKeepsThePresentationBranch(): void
    {
        $submitter = $this->seedActivitiesCountScenario();

        // "id==<submitter> OR presentations_track_id==<secondaryTrack>". Phase 1 matches
        // the submitter through either branch, but phase 2 sees only the
        // presentation-level branch: Filter::toRawSQL skips the fields it has no mapping
        // for, which is the same semantics every other toRawSQL caller lives with. The
        // count is therefore the secondaryTrack presentations of the matched submitters --
        // P2 alone -- and not all three. Pinned here because it is the one case where
        // phase 2 ends up narrower than the set phase 1 matched.
        $filter = FilterParser::parse(
            ['id==' . $submitter->getId() . ',presentations_track_id==' . self::$secondaryTrack->getId()],
            ['id' => ['=='], 'presentations_track_id' => ['==']]
        );

        $count = EntityManager::getRepository(Member::class)
            ->getUniqueActivitiesCountBySummit(self::$summit, $filter);

        $this->assertEquals(1, $count);
    }
}
