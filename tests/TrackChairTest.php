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
use App\Models\Foundation\Main\IGroup;
use models\exceptions\ValidationException;

/**
 * Class TrackChairTEst
 * @package Tests
 */
class TrackChairTest extends BrowserKitTestCase
{
    use InsertSummitTestData;

    use InsertMemberTestData;

    protected function setUp():void
    {
        parent::setUp();
        self::insertSummitTestData();
        self::insertMemberTestData(IGroup::TrackChairs);
        self::$summit_permission_group->addMember(self::$member);
        self::$summit->setMarketingSiteUrl("https://test.com");
        self::$em->persist(self::$summit);
        self::$em->persist(self::$summit_permission_group);
        self::$em->flush();
    }

    protected function tearDown():void
    {
        self::clearMemberTestData();
        self::clearSummitTestData();
        parent::tearDown();
    }

    public function testAddTrackChair(){
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage(
            sprintf("Category %s is already allowed for member %s", self::$defaultTrack->getId(), (self::$member->getId())
        ));
        $trackChairs = self::$summit->addTrackChair(self::$member, [ self::$defaultTrack ]);
        $this->assertTrue(!is_null($trackChairs));
        self::$em->persist(self::$summit);
        self::$em->flush();
        $this->assertFalse(self::$summit->isTrackChairAdmin(self::$member));
        $this->assertTrue(self::$summit->isTrackChair(self::$member));
        $this->assertTrue(self::$summit->isTrackChair(self::$member, self::$defaultTrack));
        // re add
        self::$summit->addTrackChair(self::$member, [ self::$defaultTrack ] );
    }
}