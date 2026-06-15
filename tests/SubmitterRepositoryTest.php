<?php namespace Tests;
use App\ModelSerializers\IMemberSerializerTypes;
use LaravelDoctrine\ORM\Facades\EntityManager;
use models\main\Member;
use models\summit\Presentation;
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
        //   P1 + P2 — submitted by member2 (no PresentationSpeaker entity → is_speaker==false)
        //   P3      — submitted by member, who is also a speaker on that same presentation,
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

        // is_speaker==false: P1 and P2 only — member2 is never both creator and speaker
        // on the same presentation.  P3 is excluded because member is a speaker on their
        // own submission.
        $filter = FilterParser::parse(
            ['filter' => 'is_speaker==false'],
            ['is_speaker' => ['==']]
        );
        $filteredCount = $submitter_repository->getUniqueActivitiesCountBySummit(self::$summit, $filter);
        self::assertEquals(2, $filteredCount);
    }
}
