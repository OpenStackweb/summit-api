<?php namespace Tests\unit;

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

use models\main\SummitAuditLog;
use LaravelDoctrine\ORM\Facades\EntityManager;
use Tests\BrowserKitTestCase;
use models\main\Member;
use models\summit\Summit;

/**
 * Class SummitAuditLogTest
 * @package Tests\unit
 */
class SummitAuditLogTest extends BrowserKitTestCase
{
    public function test()
    {
        $member_repo = EntityManager::getRepository(Member::class);
        $member = $member_repo->find(3);

        $summit_repo = EntityManager::getRepository(Summit::class);
        $summit = $summit_repo->find(56);

        $log = new SummitAuditLog($member, "UNIT_TEST", $summit);

        EntityManager::persist($log);
        EntityManager::flush();
        EntityManager::clear();

        $repo = EntityManager::getRepository(SummitAuditLog::class);
        $found_log = $repo->find($log->getId());

        $this->assertInstanceOf(SummitAuditLog::class, $found_log);
        $this->assertEquals($member->getEmail(), $found_log->getUser()->getEmail());
        $this->assertEquals($summit->getName(), $found_log->getSummit()->getName());
        $this->assertEquals("UNIT_TEST", $found_log->getAction());
    }
}