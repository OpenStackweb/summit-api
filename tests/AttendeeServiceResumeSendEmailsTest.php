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

use App\Jobs\Emails\Registration\Attendees\GenericSummitAttendeeEmail;
use App\Jobs\Emails\SummitAttendeeTicketRegenerateHashEmail;
use App\Models\Foundation\Main\IGroup;
use App\Services\Model\IAttendeeService;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Queue;
use LaravelDoctrine\ORM\Facades\Registry;
use models\summit\SummitAttendeeAnnouncementEmail;
use models\utils\SilverstripeBaseModel;
use ReflectionProperty;

/**
 * Covers AttendeeService::send()'s resume-skip check (wired in Task 3): on a retried chunk
 * (payload carries resume_since), an attendee - or, for the ticket flow event, a single ticket -
 * whose sent proof was written at or after resume_since is skipped, while everyone else in the
 * chunk is processed exactly as a first attempt would. With no resume_since (first attempt),
 * behavior is unchanged from before Task 3: nothing is skipped, and exactly one proof row is
 * written per email actually dispatched.
 *
 * Class AttendeeServiceResumeSendEmailsTest
 */
final class AttendeeServiceResumeSendEmailsTest extends TestCase
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

    private function em()
    {
        return Registry::getManager(SilverstripeBaseModel::EntityManager);
    }

    private function service(): IAttendeeService
    {
        return App::make(IAttendeeService::class);
    }

    /**
     * @param int $attendee_id
     * @param string $type
     * @param \DateTime|null $backdateTo null marks it sent "now"
     * @param int|null $ticket_id
     */
    private function givenAttendeeHasProof(int $attendee_id, string $type, ?\DateTime $backdateTo = null, ?int $ticket_id = null): void
    {
        $em = $this->em();
        $attendee = $em->getRepository(\models\summit\SummitAttendee::class)->find($attendee_id);
        $summit = $em->getRepository(\models\summit\Summit::class)->find(self::$summit->getId());

        $proof = new SummitAttendeeAnnouncementEmail();
        $proof->setType($type);
        $proof->setSummit($summit);
        if (!is_null($ticket_id)) {
            $ticket = $em->getRepository(\models\summit\SummitAttendeeTicket::class)->find($ticket_id);
            $proof->setTicket($ticket);
        }
        $attendee->addAnnouncementEmail($proof);

        if (is_null($backdateTo)) {
            $proof->markAsSent();
        } else {
            $prop = new ReflectionProperty(SummitAttendeeAnnouncementEmail::class, 'send_date');
            $prop->setAccessible(true);
            $prop->setValue($proof, $backdateTo);
        }

        $em->persist($proof);
        $em->flush();
    }

    private function proofCount(int $attendee_id, string $type, ?int $ticket_id = null): int
    {
        $em = $this->em();
        $qb = $em->createQueryBuilder()
            ->select('COUNT(p.id)')
            ->from(SummitAttendeeAnnouncementEmail::class, 'p')
            ->where('p.attendee = :attendee_id')
            ->andWhere('p.type = :type')
            ->setParameter('attendee_id', $attendee_id)
            ->setParameter('type', $type);
        if (is_null($ticket_id)) {
            $qb->andWhere('p.ticket IS NULL');
        } else {
            $qb->andWhere('p.ticket = :ticket_id')->setParameter('ticket_id', $ticket_id);
        }
        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    public function testFirstAttemptWithNoResumeSinceDispatchesAndRecordsExactlyOneProof(): void
    {
        Queue::fake();

        $attendee = self::$summit->getAttendees()->first();
        $attendee_id = $attendee->getId();

        $this->service()->send(self::$summit->getId(), [
            'email_flow_event' => GenericSummitAttendeeEmail::EVENT_SLUG,
            'attendees_ids'    => [$attendee_id],
        ]);

        Queue::assertPushed(GenericSummitAttendeeEmail::class, 1);
        $this->assertSame(1, $this->proofCount($attendee_id, GenericSummitAttendeeEmail::EVENT_SLUG));
    }

    public function testResumedRunSkipsAttendeeWithProofSinceDispatchAndDoesNotDuplicateProof(): void
    {
        Queue::fake();

        $attendee = self::$summit->getAttendees()->first();
        $attendee_id = $attendee->getId();

        $dispatchedAt = time() - 600;

        // proof written "now" (after dispatch) - this run already reached this attendee
        $this->givenAttendeeHasProof($attendee_id, GenericSummitAttendeeEmail::EVENT_SLUG, null);

        $this->service()->send(self::$summit->getId(), [
            'email_flow_event' => GenericSummitAttendeeEmail::EVENT_SLUG,
            'attendees_ids'    => [$attendee_id],
            'dispatched_at'    => $dispatchedAt,
            'resume_since'     => $dispatchedAt,
        ]);

        Queue::assertNotPushed(GenericSummitAttendeeEmail::class);
        // still exactly one proof (the pre-existing one) - the resume check did not duplicate it
        $this->assertSame(1, $this->proofCount($attendee_id, GenericSummitAttendeeEmail::EVENT_SLUG));
    }

    public function testResumedRunStillProcessesAttendeeWithProofBeforeDispatch(): void
    {
        Queue::fake();

        $attendee = self::$summit->getAttendees()->first();
        $attendee_id = $attendee->getId();

        $dispatchedAt = time() - 600;

        // proof from 30 days ago - an earlier, unrelated campaign, not this run
        $this->givenAttendeeHasProof($attendee_id, GenericSummitAttendeeEmail::EVENT_SLUG, new \DateTime('-30 days', new \DateTimeZone('UTC')));

        $this->service()->send(self::$summit->getId(), [
            'email_flow_event' => GenericSummitAttendeeEmail::EVENT_SLUG,
            'attendees_ids'    => [$attendee_id],
            'dispatched_at'    => $dispatchedAt,
            'resume_since'     => $dispatchedAt,
        ]);

        Queue::assertPushed(GenericSummitAttendeeEmail::class, 1);
        // the old proof plus the new one from this run
        $this->assertSame(2, $this->proofCount($attendee_id, GenericSummitAttendeeEmail::EVENT_SLUG));
    }

    public function testResumedRunSkipsOnlyTicketWithProofSinceDispatchNotOthers(): void
    {
        Queue::fake();

        $attendee = self::$summit->getAttendees()->first();
        $attendee_id = $attendee->getId();
        $tickets = $attendee->getTickets();
        $this->assertGreaterThanOrEqual(2, $tickets->count(), 'fixture must seed at least 2 tickets for this attendee');
        $ticket_ids = [];
        foreach ($tickets as $t) {
            $ticket_ids[] = $t->getId();
        }
        $first_ticket_id = $ticket_ids[0];

        $type = SummitAttendeeTicketRegenerateHashEmail::EVENT_SLUG;
        $dispatchedAt = time() - 600;

        // only the first ticket already has a proof since dispatch
        $this->givenAttendeeHasProof($attendee_id, $type, null, $first_ticket_id);

        $this->service()->send(self::$summit->getId(), [
            'email_flow_event' => $type,
            'attendees_ids'    => [$attendee_id],
            'dispatched_at'    => $dispatchedAt,
            'resume_since'     => $dispatchedAt,
        ]);

        // the first ticket's proof is untouched (still exactly one - not duplicated)
        $this->assertSame(1, $this->proofCount($attendee_id, $type, $first_ticket_id));
        // every other ticket in the fixture must have been processed (one proof each) - the
        // resume check is per-ticket, not per-attendee, so it must not skip the whole attendee
        for ($i = 1; $i < count($ticket_ids); $i++) {
            $this->assertSame(
                1,
                $this->proofCount($attendee_id, $type, $ticket_ids[$i]),
                sprintf('ticket %s (not the resume-skipped one) must still have been processed', $ticket_ids[$i])
            );
        }
    }
}
