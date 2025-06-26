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

use App\Models\Foundation\Main\IGroup;
use models\main\SummitAuditLog;
use Tests\BrowserKitTestCase;
use Tests\InsertMemberTestData;
use Tests\InsertSummitTestData;

/**
 * Class SummitAuditLogTest
 * @package Tests\unit
 */
class SummitAuditLogTest extends BrowserKitTestCase
{
    use InsertMemberTestData;
    use InsertSummitTestData;


    /**
     * @throws \Exception
     */
    protected function setUp():void
    {
        parent::setUp();
        self::insertMemberTestData(IGroup::FoundationMembers);
        self::insertSummitTestData();
    }

    public function tearDown():void
    {
        self::clearMemberTestData();
        self::clearSummitTestData();
        parent::tearDown();
    }

    public function test()
    {

        $member = self::$member;

        $summit = self::$summit;

        $log = new SummitAuditLog($member, "UNIT_TEST", $summit);

        self::$em->persist($log);
        self::$em->flush();

        $repo = self::$em->getRepository(SummitAuditLog::class);
        $found_log = $repo->find($log->getId());

        $this->assertInstanceOf(SummitAuditLog::class, $found_log);
        $this->assertEquals($member->getEmail(),
            $found_log->getUser()->getEmail());
        $this->assertEquals($summit->getName(), $found_log->getSummit()->getName());
        $this->assertEquals("UNIT_TEST", $found_log->getAction());

        self::$em->remove($found_log);
    }
}