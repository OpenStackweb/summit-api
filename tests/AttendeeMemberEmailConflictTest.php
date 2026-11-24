<?php namespace Tests;
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

use App\Models\Foundation\Main\IGroup;
use App\Services\Model\IAttendeeService;
use App\Services\Model\IMemberService;
use Doctrine\DBAL\Connection;
use App\Jobs\UpdateAttendeeInfo;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Bus;
use LaravelDoctrine\ORM\Facades\Registry;
use models\main\Member;
use models\summit\SummitAttendee;
use models\summit\SummitAttendeeNote;
use models\utils\SilverstripeBaseModel;

/**
 * Class AttendeeMemberEmailConflictTest
 *
 * A member's email changes to the email of a member-less attendee of the same summit, so the
 * member's attendee (old email) and the member-less one (new email) can not be reconciled
 * without hitting SummitAttendee_Email_SummitID / SummitAttendee_Member_Summit.
 */
final class AttendeeMemberEmailConflictTest extends TestCase
{
    use InsertSummitTestData;

    use InsertMemberTestData;

    private const MemberlessEmail = 'memberless@test.com';

    private const NewFirstName = 'Renamed';

    private string $old_email;

    private int $member_attendee_id;

    private int $memberless_attendee_id;

    private int $note_id;

    protected function setUp(): void
    {
        parent::setUp();

        self::insertMemberTestData(IGroup::TrackChairs);
        self::$defaultMember = self::$member;
        self::insertSummitTestData();

        $em = $this->em();
        $this->old_email = self::$member->getEmail();

        $member_attendee = self::$summit->getAttendeeByMember(self::$member);
        $memberless_attendee = self::$summit->getAttendeeByEmailAndMemberNotSet(self::MemberlessEmail);
        $this->assertNotNull($member_attendee);
        $this->assertNotNull($memberless_attendee);

        $note = new SummitAttendeeNote('Note on the member-less attendee', $memberless_attendee);
        $memberless_attendee->addNote($note);
        $em->persist($note);
        $em->flush();

        $this->member_attendee_id = $member_attendee->getId();
        $this->memberless_attendee_id = $memberless_attendee->getId();
        $this->note_id = $note->getId();
    }

    protected function tearDown(): void
    {
        self::clearSummitTestData();
        self::clearMemberTestData();
        parent::tearDown();
    }

    private function em(): \Doctrine\ORM\EntityManagerInterface
    {
        $em = Registry::getManager(SilverstripeBaseModel::EntityManager);
        if (!$em->isOpen()) {
            $em = Registry::resetManager(SilverstripeBaseModel::EntityManager);
        }
        return $em;
    }

    private function db(): Connection
    {
        return $this->em()->getConnection();
    }

    /**
     * the member email changes to the member-less attendee's one and the first name changes too
     */
    private function changeMemberEmail(bool $verified, string $email = self::MemberlessEmail): void
    {
        $em = $this->em();
        $member = $em->getRepository(Member::class)->find(self::$member->getId());
        $member->setEmail($email);
        $member->setEmailVerified($verified);
        $member->setFirstName(self::NewFirstName);
        $em->flush();
        $em->clear();
    }

    private function attendeeRow(int $id): array|false
    {
        return $this->db()->fetchAssociative(
            'SELECT ID, Email, FirstName, MemberID FROM SummitAttendee WHERE ID = ?',
            [$id]
        );
    }

    private function ticketsCount(int $attendee_id): int
    {
        return (int)$this->db()->fetchOne('SELECT COUNT(ID) FROM SummitAttendeeTicket WHERE OwnerID = ?', [$attendee_id]);
    }

    private function assertMemberAttendeeKeptOldEmail(): void
    {
        $row = $this->attendeeRow($this->member_attendee_id);
        $this->assertEquals($this->old_email, $row['Email']);
        $this->assertEquals(self::NewFirstName, $row['FirstName']);
        $this->assertEquals(self::$member->getId(), $row['MemberID']);
    }

    public function testUpdateAttendeesByMemberIdVerifiedEmailMergesMemberlessAttendee(): void
    {
        $this->changeMemberEmail(true);

        App::make(IAttendeeService::class)->updateAttendeesByMemberId(self::$member->getId());

        $rows = $this->db()->fetchAllAssociative(
            'SELECT ID, MemberID, FirstName FROM SummitAttendee WHERE SummitID = ? AND Email = ?',
            [self::$summit->getId(), self::MemberlessEmail]
        );
        $this->assertCount(1, $rows);
        $this->assertEquals($this->member_attendee_id, $rows[0]['ID']);
        $this->assertEquals(self::$member->getId(), $rows[0]['MemberID']);
        $this->assertEquals(self::NewFirstName, $rows[0]['FirstName']);
        // its own 5 plus the member-less attendee's 1
        $this->assertEquals(6, $this->ticketsCount($this->member_attendee_id));

        $this->assertFalse($this->attendeeRow($this->memberless_attendee_id));
        $this->assertEquals(
            0,
            (int)$this->db()->fetchOne('SELECT COUNT(ID) FROM SummitAttendeeNote WHERE ID = ?', [$this->note_id])
        );
    }

    public function testUpdateAttendeesByMemberIdUnverifiedEmailKeepsBothAttendees(): void
    {
        $this->changeMemberEmail(false);

        App::make(IAttendeeService::class)->updateAttendeesByMemberId(self::$member->getId());

        $this->assertMemberAttendeeKeptOldEmail();

        $row = $this->attendeeRow($this->memberless_attendee_id);
        $this->assertEquals(self::MemberlessEmail, $row['Email']);
        $this->assertNull($row['MemberID']);
        $this->assertEquals(1, $this->ticketsCount($this->memberless_attendee_id));
    }

    public function testUpdateAttendeesByMemberIdEmailOwnedByAnotherMemberLeavesThatAttendeeUnchanged(): void
    {
        // the attendee that has the email belongs to another member
        $this->db()->executeStatement(
            'UPDATE SummitAttendee SET MemberID = ? WHERE ID = ?',
            [self::$member2->getId(), $this->memberless_attendee_id]
        );
        $before = $this->attendeeRow($this->memberless_attendee_id);

        $this->changeMemberEmail(true);

        App::make(IAttendeeService::class)->updateAttendeesByMemberId(self::$member->getId());

        $this->assertMemberAttendeeKeptOldEmail();
        $this->assertEquals($before, $this->attendeeRow($this->memberless_attendee_id));
        $this->assertEquals(1, $this->ticketsCount($this->memberless_attendee_id));
    }

    public function testAssocSummitOrdersDoesNotLinkMemberlessAttendeeToMemberWithAttendee(): void
    {
        $this->changeMemberEmail(true);

        App::make(IMemberService::class)->assocSummitOrders(self::$member->getId());

        $row = $this->attendeeRow($this->memberless_attendee_id);
        $this->assertNotFalse($row);
        $this->assertNull($row['MemberID']);
        $this->assertEquals($this->old_email, $this->attendeeRow($this->member_attendee_id)['Email']);
    }

    public function testRepairCommandDispatchesOnlyVerifiedMembers(): void
    {
        $this->changeMemberEmail(true);
        Bus::fake();

        // dry run: lists the pair, dispatches nothing
        Artisan::call('summit:repair-attendee-member-email-pairs');
        $this->assertStringContainsString('1 pairs', Artisan::output());
        Bus::assertNotDispatched(UpdateAttendeeInfo::class);

        Artisan::call('summit:repair-attendee-member-email-pairs', ['--dispatch' => true]);
        $member_id = self::$member->getId();
        Bus::assertDispatched(UpdateAttendeeInfo::class, function (UpdateAttendeeInfo $job) use ($member_id) {
            return (fn() => $this->member_id)->call($job) == $member_id;
        });
        Bus::assertDispatchedTimes(UpdateAttendeeInfo::class, 1);
    }

    public function testRepairCommandSkipsUnverifiedMembers(): void
    {
        $this->changeMemberEmail(false);
        Bus::fake();

        Artisan::call('summit:repair-attendee-member-email-pairs', ['--dispatch' => true]);

        $this->assertStringContainsString('1 pairs', Artisan::output());
        Bus::assertNotDispatched(UpdateAttendeeInfo::class);
    }

    public function testRepairCommandListsPairIdentifiers(): void
    {
        $this->changeMemberEmail(false);

        Artisan::call('summit:repair-attendee-member-email-pairs');
        $output = Artisan::output();

        $this->assertStringContainsString((string)$this->member_attendee_id, $output);
        $this->assertStringContainsString((string)$this->memberless_attendee_id, $output);
        $this->assertStringNotContainsString(self::MemberlessEmail, $output);
    }

    public function testRepairCommandResumesAfterMemberId(): void
    {
        $this->changeMemberEmail(true);
        Bus::fake();

        // the member is at or before the cursor: skipped
        Artisan::call('summit:repair-attendee-member-email-pairs', [
            '--dispatch' => true,
            '--after-member-id' => self::$member->getId(),
        ]);
        Bus::assertNotDispatched(UpdateAttendeeInfo::class);

        Artisan::call('summit:repair-attendee-member-email-pairs', [
            '--dispatch' => true,
            '--after-member-id' => self::$member->getId() - 1,
        ]);
        Bus::assertDispatchedTimes(UpdateAttendeeInfo::class, 1);
        $this->assertStringContainsString(
            sprintf('--after-member-id=%s', self::$member->getId()),
            Artisan::output()
        );
    }

    public function testUpdateAttendeesByMemberIdMergesMemberlessAttendeeWithEmailNearColumnLimit(): void
    {
        // SummitAttendee.Email is varchar(100): email + "-deleted" would not fit
        $long_email = str_repeat('a', 98 - strlen('@test.com')) . '@test.com';
        $this->assertEquals(98, strlen($long_email));
        $this->db()->executeStatement(
            'UPDATE SummitAttendee SET Email = ? WHERE ID = ?',
            [$long_email, $this->memberless_attendee_id]
        );
        $this->changeMemberEmail(true, $long_email);

        App::make(IAttendeeService::class)->updateAttendeesByMemberId(self::$member->getId());

        $this->assertFalse($this->attendeeRow($this->memberless_attendee_id));
        $row = $this->attendeeRow($this->member_attendee_id);
        $this->assertEquals($long_email, $row['Email']);
        $this->assertEquals(6, $this->ticketsCount($this->member_attendee_id));
    }
}
