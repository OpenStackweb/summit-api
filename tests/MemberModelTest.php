<?php

/*
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

use Tests\InsertOrdersTestData;
use Tests\InsertSummitTestData;
use Tests\ProtectedApiTest;

/**
 * Class MemberModelTest
 */
class MemberModelTest extends ProtectedApiTest
{
    use InsertSummitTestData;

    use InsertOrdersTestData;
    protected function setUp():void
    {
        parent::setUp();
        self::$defaultMember = self::$member;
        self::$defaultMember2 = self::$member2;
        self::insertSummitTestData();
        self::InsertOrdersTestData();
    }

    public function tearDown():void
    {
        self::clearSummitTestData();
        parent::tearDown();
    }

    public function testGetPaidSummitTicketsBySummitId(){
        $member = self::$defaultMember;
        $summit = self::$summit;
        $tickets = $member->getPaidSummitTicketsBySummitId($summit->getId());
        $this->assertNotEmpty($tickets);
        foreach ($tickets as $ticket){
            $this->assertTrue($ticket->isPaid());
            $this->assertEquals($ticket->getOrder()->getSummit()->getId(),$summit->getId());
        }
    }

}