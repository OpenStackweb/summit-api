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

use models\main\SummitAttendeeBadgeAuditLog;
use LaravelDoctrine\ORM\Facades\EntityManager;
use Tests\BrowserKitTestCase;
use models\main\Member;
use models\summit\Summit;
use models\summit\SummitAttendeeBadge;

/**
 * Class SummitAttendeeBadgeAuditLogTest
 * @package Tests\unit
 */
class SummitAttendeeBadgeAuditLogTest extends BrowserKitTestCase
{
    public function test()
    {
        $member_repo = EntityManager::getRepository(Member::class);
        $member = $member_repo->find(3);

        $summit_repo = EntityManager::getRepository(Summit::class);
        $summit = $summit_repo->find(56);

        $badge_repo = EntityManager::getRepository(SummitAttendeeBadge::class);
        $badge = $badge_repo
            ->createQueryBuilder('b')
            ->join('b.ticket', 't')
            ->join('t.owner', 'm')
            ->where('m.id = :memberId')
            ->setParameter('memberId', $member->getId())
            ->getQuery()
            ->getOneOrNullResult();

        $log = new SummitAttendeeBadgeAuditLog($member, "UNIT_TEST", $summit, $badge);

        EntityManager::persist($log);
        EntityManager::flush();
        EntityManager::clear();

        $repo = EntityManager::getRepository(SummitAttendeeBadgeAuditLog::class);
        $found_log = $repo->find($log->getId());

        $this->assertInstanceOf(SummitAttendeeBadgeAuditLog::class, $found_log);
        $this->assertEquals($member->getEmail(), $found_log->getUser()->getEmail());
        $this->assertEquals($summit->getName(), $found_log->getSummit()->getName());
        $this->assertEquals("UNIT_TEST", $found_log->getAction());
        $this->assertEquals($badge->getId(), $found_log->getAttendeeBadge()->getId());
    }
}