<?php namespace Tests\Unit\Entities;
/**
 * Copyright 2026 OpenStack Foundation
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

use models\main\Member;
use models\summit\SummitAttendee;
use models\summit\SummitAttendeeCheckInLog;
use PHPUnit\Framework\TestCase;

/**
 * Pure unit tests for the invariants enforced by SummitAttendeeCheckInLog::build().
 * No database, no framework boot.
 *
 * @package Tests\Unit\Entities
 */
class SummitAttendeeCheckInLogTest extends TestCase
{
    public function testBuildKeepsAllTheInformation(): void
    {
        $attendee = new SummitAttendee();
        $actor = new Member();

        $log = SummitAttendeeCheckInLog::build(
            $attendee,
            SummitAttendeeCheckInLog::ActionCheckedOut,
            SummitAttendeeCheckInLog::SourceAdminUI,
            $actor,
            'checkinapp',
            'Left the venue',
            '203.0.113.10',
            'Mozilla/5.0'
        );

        $this->assertSame($attendee, $log->getAttendee());
        $this->assertSame($actor, $log->getActor());
        $this->assertEquals('CHECKED_OUT', $log->getAction());
        $this->assertEquals('ADMIN_UI', $log->getSource());
        $this->assertEquals('checkinapp', $log->getClientId());
        $this->assertEquals('Left the venue', $log->getReason());
        $this->assertEquals('203.0.113.10', $log->getIpAddress());
        $this->assertEquals('Mozilla/5.0', $log->getUserAgent());
    }

    public function testBuildAcceptsClientIdWithoutMember(): void
    {
        $log = SummitAttendeeCheckInLog::build(
            new SummitAttendee(),
            SummitAttendeeCheckInLog::ActionCheckedIn,
            SummitAttendeeCheckInLog::SourceBadgeScan,
            null,
            'scanbadgeapp'
        );

        $this->assertNull($log->getActor());
        $this->assertEquals('scanbadgeapp', $log->getClientId());
        $this->assertNull($log->getReason());
    }

    public function testBuildRejectsMissingActorAndClient(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        SummitAttendeeCheckInLog::build(
            new SummitAttendee(),
            SummitAttendeeCheckInLog::ActionCheckedIn,
            SummitAttendeeCheckInLog::SourceBadgeScan
        );
    }

    public function testBuildRejectsEmptyClientWhenThereIsNoMember(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        SummitAttendeeCheckInLog::build(
            new SummitAttendee(),
            SummitAttendeeCheckInLog::ActionCheckedIn,
            SummitAttendeeCheckInLog::SourceBadgeScan,
            null,
            ''
        );
    }

    public function testBuildRejectsUnknownAction(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        SummitAttendeeCheckInLog::build(new SummitAttendee(), 'VIRTUAL_CHECKED_IN', SummitAttendeeCheckInLog::SourceAdminUI, new Member());
    }

    public function testBuildRejectsUnknownSource(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        SummitAttendeeCheckInLog::build(new SummitAttendee(), SummitAttendeeCheckInLog::ActionCheckedIn, 'MOBILE_APP', new Member());
    }

    public function testUserAgentIsTruncatedToColumnLength(): void
    {
        $log = SummitAttendeeCheckInLog::build(
            new SummitAttendee(),
            SummitAttendeeCheckInLog::ActionCheckedIn,
            SummitAttendeeCheckInLog::SourceAdminUI,
            new Member(),
            null,
            null,
            null,
            str_repeat('a', 1000)
        );

        $this->assertEquals(512, strlen($log->getUserAgent()));
    }
}
