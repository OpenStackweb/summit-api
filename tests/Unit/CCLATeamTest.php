<?php namespace Tests\Unit;

/**
 * Copyright 2025 OpenStack Foundation
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
use Models\Foundation\Main\CCLA\Team;
use LaravelDoctrine\ORM\Facades\EntityManager;
use Tests\BrowserKitTestCase;
use models\main\Member;
use models\main\Company;
use Tests\InsertMemberTestData;

/**
 * Class CCLATest
 * @package Tests\unit
 */
class CCLATeamTest extends BrowserKitTestCase
{
    use InsertMemberTestData;

    /**
     * @throws \Exception
     */
    protected function setUp():void
    {
        parent::setUp();
        self::insertMemberTestData(IGroup::FoundationMembers);
    }

    public function tearDown():void
    {
        self::clearMemberTestData();
        parent::tearDown();
    }

    public function test()
    {
        $member_a = self::$member;
        $member_b = self::$member;

        $company = new Company();
        $company->setName("A Company");
        self::$em->persist($company);

        $team = new Team();

        $team->setCompany($company);
        $team->setMembers([$member_a, $member_b]);
        $team->setName("A-Team");

        self::$em->persist($team);
        self::$em->flush();

        $repo = self::$em->getRepository(Team::class);
        $found_team = $repo->find($team->getId());

        $this->assertInstanceOf(Team::class, $found_team);
        $this->assertEquals($member_a->getEmail(), $found_team->getMembers()[0]->getEmail());
        $this->assertEquals($company->getName(), $found_team->getCompany()->getName());
        $this->assertEquals("A-Team", $found_team->getName());

        self::$em->remove($team);
        self::$em->remove($company);
    }
}