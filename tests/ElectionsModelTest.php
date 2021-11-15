<?php namespace Tests;
/**
 * Copyright 2021 OpenStack Foundation
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
use App\Models\Foundation\Elections\Election;
use App\Models\Foundation\Main\IGroup;
use LaravelDoctrine\ORM\Facades\EntityManager;
use Libs\ModelSerializers\AbstractSerializer;
use models\exceptions\ValidationException;
use models\summit\SummitEvent;

/**
 * Class ElectionsModelTest
 * @package Tests
 */
class ElectionsModelTest extends BrowserKitTestCase
{
    use InsertMemberTestData;

    protected function setUp():void
    {
        parent::setUp();
        self::insertMemberTestData(IGroup::FoundationMembers);
        self::$em->flush();
    }

    protected function tearDown():void
    {
        self::clearMemberTestData();
        parent::tearDown();
    }

    public function testCreateCandidate(){

        $election = new Election();
        $election->setName("TEST ELECTION");
        $now = new \DateTime("now", new \DateTimeZone("UTC"));
        $election->setNominationOpens($now);
        $election->setNominationCloses((clone $now)->add(new \DateInterval("P10D")));
        $election->setNominationDeadline((clone $now)->add(new \DateInterval("P2D")));

        self::$em->persist($election);
        self::$em->flush();

        $election->createCandidancy(self::$member);

        self::$em->persist($election);
        self::$em->flush();

        $this->assertTrue($election->isCandidate(self::$member));
    }

    public function testCreateCandidateTwice(){
        $election = new Election();
        $election->setName("TEST ELECTION");
        $now = new \DateTime("now", new \DateTimeZone("UTC"));
        $election->setNominationOpens($now);
        $election->setNominationCloses((clone $now)->add(new \DateInterval("P10D")));
        $election->setNominationDeadline((clone $now)->add(new \DateInterval("P2D")));

        self::$em->persist($election);
        self::$em->flush();

        $election->createCandidancy(self::$member);

        self::$em->persist($election);
        self::$em->flush();

        $this->assertTrue($election->isCandidate(self::$member));

        $this->expectException(ValidationException::class);
        $election->createCandidancy(self::$member);
    }

    public function testCompleteCandidate(){
        $election = new Election();
        $election->setName("TEST ELECTION");
        $now = new \DateTime("now", new \DateTimeZone("UTC"));
        $election->setNominationOpens($now);
        $election->setNominationCloses((clone $now)->add(new \DateInterval("P10D")));
        $election->setNominationDeadline((clone $now)->add(new \DateInterval("P2D")));

        self::$em->persist($election);
        self::$em->flush();

        self::$member->setBio("THIS IS A TEST");

        $profile = $election->createCandidancy(self::$member);
        $profile->setBoardsRole("THIS IS A TEST");
        $profile->setExperience("THIS IS A TEST");
        $profile->setRelationshipToOpenstack("THIS IS A TEST");
        $profile->setTopPriority("THIS IS A TEST");

        self::$em->persist($election);
        self::$em->flush();

        $this->assertTrue($election->isCandidate(self::$member));
        $this->assertTrue($profile->isHasAcceptedNomination());
    }

    public function testRepository(){

        $election = new Election();
        $election->setName("TEST ELECTION");
        $now = new \DateTime("now", new \DateTimeZone("UTC"));
        $election->setNominationOpens($now);
        $election->setNominationCloses((clone $now)->add(new \DateInterval("P10D")));
        $election->setNominationDeadline((clone $now)->add(new \DateInterval("P2D")));
        $election->setOpens((clone $now)->add(new \DateInterval("P20D")));
        $election->setCloses((clone $now)->add(new \DateInterval("P21D")));

        self::$em->persist($election);
        self::$em->flush();

        $repository = EntityManager::getRepository(Election::class);

        $currentElection = $repository->getCurrent();

        $this->assertTrue(!is_null($currentElection));
    }

    public function testSerializer(){
        $fields = ['candidate_profile.election.name'];
        $res = AbstractSerializer::filterFieldsByPrefix($fields, "candidate_profile");
        $this->assertTrue(count($res) == 1);
        $this->assertTrue($res[0] == 'election.name');
        $res = AbstractSerializer::filterFieldsByPrefix($res, "election");
        $this->assertTrue(count($res) == 1);
        $this->assertTrue($res[0] == 'name');
    }
}