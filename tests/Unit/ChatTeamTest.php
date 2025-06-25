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
use models\main\ChatTeam;
use LaravelDoctrine\ORM\Facades\EntityManager;
use models\main\ChatTeamInvitation;
use models\main\ChatTeamMember;
use models\main\ChatTeamPushNotificationMessage;
use Tests\BrowserKitTestCase;
use models\main\Member;
use Tests\InsertMemberTestData;

/**
 * Class ChatTeamTest
 * @package Tests\unit
 */
class ChatTeamTest extends BrowserKitTestCase
{

    use InsertMemberTestData;
    protected function setUp(): void
    {
        parent::setUp();
        self::insertMemberTestData(IGroup::FoundationMembers);
    }

    protected function tearDown(): void
    {
        self::clearMemberTestData();
        parent::tearDown();
    }

    public function test()
    {
        $member = self::$member;
        $member2 = self::$member2;

        $message = new ChatTeamPushNotificationMessage();
        $invitation = new ChatTeamInvitation();
        $invitation->setInvitee($member2);
        $chat_member = new ChatTeamMember();
        $chat_member->setMember($member);

        $team = new ChatTeam();
        $team->addMember($chat_member);
        $team->addMessage($message);
        $team->addInvitation($invitation);

        self::$em->persist($team);
        self::$em->flush();

        $repo = self::$em->getRepository(ChatTeam::class);
        $found = $repo->find($team->getId());

        $this->assertInstanceOf(ChatTeam::class, $found);
        $this->assertEquals($found->isMember($member), true);
        $this->assertEquals($found->isAlreadyInvited($invitation->getInvitee()), true);
        $this->assertEquals($found->getMessages()->contains($message), true);

        self::$em->remove($team);
    }
}