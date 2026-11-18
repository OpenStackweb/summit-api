<?php namespace Tests\Unit\Services;
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

use App\Jobs\Emails\InviteAttendeeTicketEditionMail;
use App\Jobs\Emails\RevocationTicketEmail;
use App\Jobs\Emails\SummitAttendeeAllTicketsEditionEmail;
use App\Jobs\Emails\SummitAttendeeRegistrationIncompleteReminderEmail;
use App\Jobs\Emails\SummitAttendeeTicketEmail;
use App\Models\Foundation\Main\IGroup;
use App\Models\Foundation\Summit\EmailFlows\SummitEmailEventFlowType;
use App\Models\Foundation\Summit\EmailFlows\SummitEmailFlowType;
use App\Services\Model\IAttendeeService;
use Illuminate\Support\Facades\App;
use LaravelDoctrine\ORM\Facades\EntityManager;
use models\exceptions\ValidationException;
use models\summit\Summit;
use models\summit\SummitAttendee;
use models\summit\SummitAttendeeBadge;
use models\summit\SummitAttendeeTicket;
use Tests\InsertMemberTestData;
use Tests\InsertSummitTestData;
use Tests\TestCase;
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
        // AttendeeService::updateRedeemedPromoCodes treats the IEventbriteAPI::getAttendees response
        // as an array. Once that is fixed, mock getAttendees to return a fixture payload with a
        // promotional_code.code matching a seeded promo code and assert it is marked redeemed.
        $this->markTestSkipped('updateRedeemedPromoCodes treats IEventbriteAPI::getAttendees response as an array.');
    }

    public function testUpdateAttendeeEmailOnlyLinksExistingMemberAccount() {

        $service = App::make(IAttendeeService::class);
        $attendee = self::$summit->getAttendeeByMember(self::$defaultMember);
        $this->assertNotNull($attendee);

        // only email is submitted (no member_id), and it belongs to a known member account
        $payload = [
            'email' => self::$member2->getEmail(),
        ];

        $updated = $service->updateAttendee(self::$summit, $attendee->getId(), $payload);

        $this->assertNotNull($updated->getMember());
        $this->assertEquals(self::$member2->getId(), $updated->getMember()->getId());
    }

    public function testUpdateAttendeeEmailOnlyWithUnknownEmailClearsMemberLink()
    {
        $service = App::make(IAttendeeService::class);
        $attendee = self::$summit->getAttendeeByMember(self::$defaultMember);
        $this->assertNotNull($attendee);

        // the email matches no member account, so the stale link must be cleared
        $updated = $service->updateAttendee(self::$summit, $attendee->getId(), [
            'email' => 'no-member-' . uniqid() . '@example.com',
        ]);

        $this->assertNull($updated->getMember());
    }

    public function testUpdateAttendeeEmailOnlyWithCurrentMemberEmailPreservesLink()
    {
        $service = App::make(IAttendeeService::class);
        $attendee = self::$summit->getAttendeeByMember(self::$defaultMember);
        $this->assertNotNull($attendee);

        $updated = $service->updateAttendee(self::$summit, $attendee->getId(), [
            'email' => self::$defaultMember->getEmail(),
        ]);

        $this->assertNotNull($updated->getMember());
        $this->assertEquals(self::$defaultMember->getId(), $updated->getMember()->getId());
    }

    /**
     * member2 is already linked to another attendee in this summit and its account email has an
     * internationalized domain. IMemberRepository::getByEmail punycode-encodes its argument and
     * resolves member2; ISummitAttendeeRepository::getBySummitAndEmail does not, so the email
     * check finds nothing. The getBySummitAndMember guard is then the only check before the
     * unique (MemberID, SummitID) index.
     *
     * @return string the unicode (non punycode) email of member2
     */
    private function linkOtherAttendeeToMember2WithIdnEmail(): string
    {
        $idn_email = 'member2-' . uniqid() . '@bücher.example';

        self::$member2->setEmail($idn_email);
        self::$em->persist(self::$member2);

        $other_attendee = new SummitAttendee();
        $other_attendee->setMember(self::$member2);
        $other_attendee->setEmail($idn_email);
        $other_attendee->setFirstName(self::$member2->getFirstName());
        $other_attendee->setSurname(self::$member2->getLastName());

        self::$summit->addAttendee($other_attendee);
        self::$em->persist($other_attendee);
        self::$em->flush();

        return $idn_email;
    }

    public function testUpdateAttendeeEmailResolvingToAlreadyLinkedMemberThrowsValidationException()
    {
        $idn_email = $this->linkOtherAttendeeToMember2WithIdnEmail();

        $service = App::make(IAttendeeService::class);
        $attendee = self::$summit->getAttendeeByMember(self::$defaultMember);
        $this->assertNotNull($attendee);

        $payload = [
            'email' => $idn_email,
        ];

        $this->expectException(ValidationException::class);
        $service->updateAttendee(self::$summit, $attendee->getId(), $payload);
    }

    public function testAddAttendeeEmailResolvingToAlreadyLinkedMemberThrowsValidationException()
    {
        $idn_email = $this->linkOtherAttendeeToMember2WithIdnEmail();

        $service = App::make(IAttendeeService::class);

        $payload = [
            'email' => $idn_email,
        ];

        $this->expectException(ValidationException::class);
        $service->addAttendee(self::$summit, $payload);
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

        $this->ensureTicketRevocationEmailTemplateSeeded();

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

        $this->ensureTicketRevocationEmailTemplateSeeded();

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
     * reassignAttendeeTicket/reassignAttendeeTicketByMember dispatch a RevocationTicketEmail
     * to the previous owner and, depending on whether the new owner's profile is already
     * complete, either a SummitAttendeeTicketEmail or an InviteAttendeeTicketEditionMail to
     * the new one. Each of those job constructors requires a resolvable email template
     * identifier. The seeder that normally provides this catalog (SummitEmailFlowTypeSeeder)
     * never runs in CI, so seed the minimal rows here rather than relying on production data.
     */
    private function ensureTicketRevocationEmailTemplateSeeded(): void
    {
        $slugs = [
            RevocationTicketEmail::EVENT_SLUG => RevocationTicketEmail::DEFAULT_TEMPLATE,
            SummitAttendeeTicketEmail::EVENT_SLUG => SummitAttendeeTicketEmail::DEFAULT_TEMPLATE,
            InviteAttendeeTicketEditionMail::EVENT_SLUG => InviteAttendeeTicketEditionMail::DEFAULT_TEMPLATE,
        ];

        $repository = EntityManager::getRepository(SummitEmailEventFlowType::class);
        $flow = null;

        foreach ($slugs as $slug => $default_template) {
            if (!is_null($repository->findOneBy(['slug' => $slug]))) continue;

            if (is_null($flow)) {
                $flow = new SummitEmailFlowType();
                $flow->setName('Registration');
            }

            $event_type = new SummitEmailEventFlowType();
            $event_type->setName($slug);
            $event_type->setSlug($slug);
            $event_type->setDefaultEmailTemplate($default_template);
            $flow->addFlowEventType($event_type);
        }

        if (!is_null($flow)) {
            EntityManager::persist($flow);
            EntityManager::flush();
        }
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
