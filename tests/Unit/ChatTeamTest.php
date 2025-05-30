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

use models\main\ChatTeam;
use LaravelDoctrine\ORM\Facades\EntityManager;
use models\main\ChatTeamInvitation;
use models\main\ChatTeamPushNotificationMessage;
use Tests\BrowserKitTestCase;
use models\main\Member;
use models\summit\Summit;

/**
 * Class ChatTeamTest
 * @package Tests\unit
 */
class ChatTeamTest extends BrowserKitTestCase
{
    public function test()
    {
        $member_repo = EntityManager::getRepository(Member::class);
        $member = $member_repo->find(3);

        $message_repo = EntityManager::getRepository(ChatTeamPushNotificationMessage::class);
        $message = $message_repo->findAll()[0];

        $invitation_repo = EntityManager::getRepository(ChatTeamInvitation::class);
        $invitation = $invitation_repo->findAll()[0];

        $team = new ChatTeam();
        $team->addMember($member);
        $team->addMessage($message);
        $team->addInvitation($invitation);

        EntityManager::persist($team);
        EntityManager::flush();
        EntityManager::clear();

        $repo = EntityManager::getRepository(ChatTeam::class);
        $found = $repo->find($team->getId());

        $this->assertInstanceOf(ChatTeam::class, $found);
        $this->assertEquals($found->isMember($member), true);
        $this->assertEquals($found->isAlreadyInvited($invitation->getInvitee()), true);
        $this->assertEquals($found->getMessages()->contains($invitation), true);
    }
}