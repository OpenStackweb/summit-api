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

use Models\Foundation\Main\CCLA\Team;
use LaravelDoctrine\ORM\Facades\EntityManager;
use Tests\BrowserKitTestCase;
use models\main\Member;
use models\main\Company;

/**
 * Class CCLATest
 * @package Tests\unit
 */
class CCLATeamTest extends BrowserKitTestCase
{
    public function test()
    {
        $member_repo = EntityManager::getRepository(Member::class);
        $member_a = $member_repo->find(3);
        $member_b = $member_repo->find(5);

        $company_repo = EntityManager::getRepository(Company::class);
        $company = $company_repo->find(56);

        $team = new Team();

        $team->setCompany($company);
        $team->setMembers([$member_a, $member_b]);
        $team->setName("A-Team");

        EntityManager::persist($team);
        EntityManager::flush();
        EntityManager::clear();

        $repo = EntityManager::getRepository(Team::class);
        $found_team = $repo->find($team->getId());

        $this->assertInstanceOf(Team::class, $found_team);
        $this->assertEquals($member_a->getEmail(), $found_team->getMembers()[0]->getEmail());
        $this->assertEquals($company->getName(), $found_team->getCompany()->getName());
        $this->assertEquals("A-Team", $found_team->getName());
    }
}