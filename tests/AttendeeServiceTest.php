<?php namespace Tests;
/**
 * Copyright 2018 OpenStack Foundation
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

use App\Jobs\Emails\SummitAttendeeAllTicketsEditionEmail;
use App\Jobs\Emails\SummitAttendeeRegistrationIncompleteReminderEmail;
use App\Models\Foundation\Main\IGroup;
use App\Services\Model\IAttendeeService;
use Illuminate\Support\Facades\App;
use LaravelDoctrine\ORM\Facades\EntityManager;
use models\summit\Summit;
use models\summit\SummitAttendeeBadge;
use models\summit\SummitAttendeeTicket;
/**
 * Class AttendeeServiceTest
 */
final class AttendeeServiceTest extends TestCase
{
    use InsertSummitTestData;

    use InsertMemberTestData;

    protected function setUp(): void
    {
        parent::setUp();

        self::insertMemberTestData(IGroup::TrackChairs);
        self::$defaultMember = self::$member;
        self::insertSummitTestData();
    }

    protected function tearDown(): void
    {
        self::clearSummitTestData();
        self::clearMemberTestData();
        parent::tearDown();
    }

    public function testRedeemPromoCodes(){

        $service = App::make(IAttendeeService::class);
        $repo   =  EntityManager::getRepository(\models\summit\Summit::class);
        $summit = $repo->getById(24);
        $service->updateRedeemedPromoCodes($summit);
    }

    public function testSendAllAttendeeTickets() {

        $service = App::make(IAttendeeService::class);

        $payload = [
            "email_flow_event"  => SummitAttendeeAllTicketsEditionEmail::EVENT_SLUG,
        ];

        $service->send(self::$summit->getId(), $payload);
    }

    public function testSendAllAttendeeTicketsByAttendeeIds() {

        $service = App::make(IAttendeeService::class);

        $payload = [
            "email_flow_event"  => SummitAttendeeAllTicketsEditionEmail::EVENT_SLUG,
            "attendees_ids"     => [self::$summit->getAttendees()[0]->getId()],
        ];

        $service->send(self::$summit->getId(), $payload);
    }

    public function testSendRegistrationIncompleteReminderByAttendeeIds() {

        $service = App::make(IAttendeeService::class);

        $payload = [
            "email_flow_event"  => SummitAttendeeRegistrationIncompleteReminderEmail::EVENT_SLUG,
            "attendees_ids"     => [self::$summit->getAttendees()[0]->getId()],
        ];

        $service->send(self::$summit->getId(), $payload);
    }

    public function testReassignAttendeeTicketRegeneratesBadgeQRCode(){

        $attendee = self::$summit->getAttendeeByMember(self::$defaultMember);
        $this->assertNotNull($attendee);
        $ticket = $attendee->getTickets()->first();
        $this->assertNotNull($ticket);
        $this->assertTrue($ticket->hasBadge());
        $badge_id = $ticket->getBadge()->getId();

        $summit_id       = self::$summit->getId();
        $attendee_id     = $attendee->getId();
        $member2_email   = self::$member2->getEmail();
        $member2_first   = self::$member2->getFirstName();
        $member2_last    = self::$member2->getLastName();
        $member2_fullname = self::$member2->getFullName();
        $default_email   = self::$defaultMember->getEmail();

        // clear the identity map so the service performs a genuinely fresh load of the
        // ticket, matching how a real HTTP request behaves.
        EntityManager::clear();

        $summit   = EntityManager::getRepository(Summit::class)->find($summit_id);
        $attendee = EntityManager::getRepository(\models\summit\SummitAttendee::class)->find($attendee_id);
        $ticket_id = $this->resolveRealTicketIdForBadge($badge_id);

        $service = App::make(IAttendeeService::class);
        $payload = [
            'attendee_email'      => $member2_email,
            'attendee_first_name' => $member2_first,
            'attendee_last_name'  => $member2_last,
        ];

        $reassigned_ticket = $service->reassignAttendeeTicket($summit, $attendee, $ticket_id, $payload);

        $this->assertBadgeQRRegeneratedForNewOwner(
            $reassigned_ticket, $badge_id, $summit, $member2_email, $member2_fullname, $default_email
        );
    }

    public function testReassignAttendeeTicketByMemberRegeneratesBadgeQRCode(){

        $attendee = self::$summit->getAttendeeByMember(self::$defaultMember);
        $this->assertNotNull($attendee);
        $ticket = $attendee->getTickets()->first();
        $this->assertNotNull($ticket);
        $this->assertTrue($ticket->hasBadge());
        $badge_id = $ticket->getBadge()->getId();

        $summit_id        = self::$summit->getId();
        $attendee_id      = $attendee->getId();
        $member2_id       = self::$member2->getId();
        $member2_email    = self::$member2->getEmail();
        $member2_fullname = self::$member2->getFullName();
        $default_email    = self::$defaultMember->getEmail();

        // see comments in testReassignAttendeeTicketRegeneratesBadgeQRCode
        EntityManager::clear();

        $summit   = EntityManager::getRepository(Summit::class)->find($summit_id);
        $attendee = EntityManager::getRepository(\models\summit\SummitAttendee::class)->find($attendee_id);
        $member2  = EntityManager::getRepository(\models\main\Member::class)->find($member2_id);
        $ticket_id = $this->resolveRealTicketIdForBadge($badge_id);

        $service = App::make(IAttendeeService::class);
        $reassigned_ticket = $service->reassignAttendeeTicketByMember($summit, $attendee, $member2, $ticket_id);

        $this->assertBadgeQRRegeneratedForNewOwner(
            $reassigned_ticket, $badge_id, $summit, $member2_email, $member2_fullname, $default_email
        );
    }

    /**
     * The fixture (InsertSummitTestData) reuses one SummitAttendeeBadge PHP object
     * across several tickets, so only the LAST ticket it was attached to is the one
     * actually persisted as this badge's TicketID in the DB - resolve the real ticket
     * via the badge's own association rather than trusting collection order.
     */
    private function resolveRealTicketIdForBadge(int $badge_id): int
    {
        return EntityManager::getRepository(SummitAttendeeBadge::class)->find($badge_id)->getTicket()->getId();
    }

    /**
     * Shared post-reassignment assertions: the returned ticket's own badge
     * association must already reflect the regenerated badge (not stale/absent),
     * since API responses serialize this same object directly without a reload;
     * and the persisted badge, once reloaded independently, decodes to the new
     * owner's email/full name.
     */
    private function assertBadgeQRRegeneratedForNewOwner(
        SummitAttendeeTicket $reassigned_ticket,
        int $badge_id,
        Summit $summit,
        string $new_owner_email,
        string $new_owner_fullname,
        string $previous_owner_email
    ): void {
        $this->assertTrue($reassigned_ticket->hasBadge());
        $this->assertEquals($badge_id, $reassigned_ticket->getBadge()->getId());
        $this->assertNotEmpty($reassigned_ticket->getBadge()->getQRCode());

        EntityManager::clear();
        $badge = EntityManager::getRepository(SummitAttendeeBadge::class)->find($badge_id);
        $qr_code = $badge->getQRCode();
        $this->assertNotEmpty($qr_code);
        $decoded = SummitAttendeeBadge::parseQRCode(SummitAttendeeBadge::decodeQRCodeFor($summit, $qr_code));

        $this->assertEquals($new_owner_email, $decoded['owner_email']);
        $this->assertEquals($new_owner_fullname, $decoded['owner_fullname']);
        $this->assertNotEquals($previous_owner_email, $decoded['owner_email']);
    }
}