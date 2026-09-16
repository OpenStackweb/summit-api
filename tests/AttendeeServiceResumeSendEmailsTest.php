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
use App\Services\Model\IAttendeeService;
use App\Services\utils\IEmailExcerptService;
use App\Services\Utils\Facades\EmailExcerpt;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Bus;
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
final class AttendeeServiceResumeSendEmailsTest extends ProtectedApiTestCase
{
    use InsertSummitTestData;

    protected function setUp(): void
    {
        parent::setUp();

        self::$defaultMember = self::$member;
        self::insertSummitTestData();
    }

    protected function tearDown(): void
    {
        self::clearSummitTestData();
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

    /**
     * The resume-skip notice is informational: it must reach the outcome excerpt through the
     * INFO callback, never the ERROR one. ParametrizedSendEmails::_sendEmails hands
     * processCurrentId its callbacks positionally as (success, error, info), so a closure that
     * declares them in a different order silently routes every skip into
     * EmailExcerpt::addErrorMessage and the operator's report shows a wall of errors for a run
     * that did exactly what it should.
     */
    public function testResumedRunReportsTheSkipAsAnInfoLineNotAnError(): void
    {
        Queue::fake();

        $attendee = self::$summit->getAttendees()->first();
        $attendee_id = $attendee->getId();
        $attendee_email = $attendee->getEmail();

        $dispatchedAt = time() - 600;

        $this->givenAttendeeHasProof($attendee_id, GenericSummitAttendeeEmail::EVENT_SLUG, null);

        $this->service()->send(self::$summit->getId(), [
            'email_flow_event' => GenericSummitAttendeeEmail::EVENT_SLUG,
            'attendees_ids'    => [$attendee_id],
            'dispatched_at'    => $dispatchedAt,
            'resume_since'     => $dispatchedAt,
        ]);

        Queue::assertNotPushed(GenericSummitAttendeeEmail::class);

        $report = EmailExcerpt::getReport();

        $skipLines = array_values(array_filter(
            $report,
            fn($line) => str_contains($line['message'] ?? '', $attendee_email)
        ));
        $this->assertCount(1, $skipLines, 'exactly one excerpt line must name the resume-skipped attendee');
        $this->assertSame(
            IEmailExcerptService::InfoType,
            $skipLines[0]['type'],
            'the resume-skip notice must be an INFO line, not an ERROR line'
        );

        $errorLines = array_filter($report, fn($line) => ($line['type'] ?? null) === IEmailExcerptService::ErrorType);
        $this->assertCount(0, $errorLines, 'a resumed run that only skipped an already-reached attendee must report no errors');
    }

    /**
     * send() loads each id with getByIdExclusiveLock, a bare find() by primary key. An explicit
     * attendees_ids payload can therefore name an attendee of a different summit than the one
     * the request was made for; nothing upstream (route middleware, CurrentSummitFinderStrategy)
     * checks that. The attendee must be skipped before any side effect: no email dispatched, no
     * sent-proof written (it would be stamped with the requesting summit's id), and the operator's
     * excerpt must carry exactly one ERROR line naming the attendee.
     */
    public function testSendSkipsAnAttendeeThatDoesNotBelongToTheRequestedSummit(): void
    {
        Queue::fake();

        $attendee = self::$summit->getAttendees()->first();
        $attendee_id = $attendee->getId();
        $this->assertNotSame(self::$summit->getId(), self::$summit2->getId(), 'fixture must provide a second, distinct summit');

        $this->service()->send(self::$summit2->getId(), [
            'email_flow_event' => GenericSummitAttendeeEmail::EVENT_SLUG,
            'attendees_ids'    => [$attendee_id],
        ]);

        Queue::assertNotPushed(GenericSummitAttendeeEmail::class);
        $this->assertSame(0, $this->proofCount($attendee_id, GenericSummitAttendeeEmail::EVENT_SLUG), 'no sent-proof may be written for an attendee of another summit');

        $errorLines = array_values(array_filter(
            EmailExcerpt::getReport(),
            fn($line) => ($line['type'] ?? null) === IEmailExcerptService::ErrorType
        ));
        $this->assertCount(1, $errorLines, 'the excerpt must carry exactly one ERROR line for the foreign attendee');
        $this->assertStringContainsString(sprintf('(%s)', $attendee_id), $errorLines[0]['message'], 'the ERROR line must name the skipped attendee');
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

    const SimulatedDispatchFailure = 'simulated transient queue push failure';

    /**
     * @return int[] every attendee of the fixture summit, in the order send() will process them
     */
    private function fixtureAttendeeIds(): array
    {
        $ids = [];
        foreach (self::$summit->getAttendees() as $attendee) {
            $ids[] = $attendee->getId();
        }
        return $ids;
    }

    /**
     * Fails the FIRST GenericSummitAttendeeEmail push with a transient queue error and lets every
     * later push succeed. The strategies dispatch mail jobs with a bare ::dispatch(), so the
     * failure surfaces inside that attendee's own send() transaction - exactly where a redis
     * outage would surface in production.
     *
     * @param array $dispatched receives the class name of every job dispatched, in order
     */
    private function givenTheFirstGenericEmailDispatchFails(array &$dispatched): void
    {
        Bus::shouldReceive('dispatch')->andReturnUsing(function ($job) use (&$dispatched) {
            $dispatched[] = get_class($job);
            $generic = count(array_filter($dispatched, fn($class) => $class === GenericSummitAttendeeEmail::class));
            if ($job instanceof GenericSummitAttendeeEmail && $generic === 1) {
                throw new \RuntimeException(self::SimulatedDispatchFailure);
            }
            return null;
        });
    }

    /**
     * Regression: the strategy used to be built with the root Summit that _sendEmails fetches once,
     * outside the per-attendee transaction. After any attendee's transaction failed,
     * DoctrineTransactionService cleared the EntityManager, that Summit became detached, and every
     * later attendee's proof failed at flush ("A new entity was found through the relationship
     * ...#summit") AFTER its email had already been dispatched - so a retried chunk re-emailed
     * everyone processed after the failure while the excerpt reported them as sent.
     */
    public function testAFailingAttendeeDoesNotPreventProofRecordingForTheRestOfTheChunk(): void
    {
        $dispatched = [];
        $this->givenTheFirstGenericEmailDispatchFails($dispatched);

        $ids = $this->fixtureAttendeeIds();
        $this->assertGreaterThanOrEqual(2, count($ids), 'fixture must seed at least 2 attendees on the summit');

        $this->service()->send(self::$summit->getId(), [
            'email_flow_event' => GenericSummitAttendeeEmail::EVENT_SLUG,
            'attendees_ids'    => $ids,
        ]);

        $generic_dispatches = count(array_filter($dispatched, fn($class) => $class === GenericSummitAttendeeEmail::class));
        $this->assertSame(count($ids), $generic_dispatches, 'every attendee must still be attempted after the failure');

        $this->assertSame(0, $this->proofCount($ids[0], GenericSummitAttendeeEmail::EVENT_SLUG), 'the attendee whose dispatch failed must have no proof');
        foreach (array_slice($ids, 1) as $id) {
            $this->assertSame(
                1,
                $this->proofCount($id, GenericSummitAttendeeEmail::EVENT_SLUG),
                sprintf('attendee %s, processed after the failure, must have exactly one proof', $id)
            );
        }
    }

    /**
     * An attendee this chunk could not process must show in the operator's outcome excerpt as an
     * ERROR line: the excerpt is the only signal the operator gets, and SpeakerService::send
     * already reports it that way. Previously the exception was only logged, so the excerpt of a
     * run that silently skipped an attendee read exactly like a clean one.
     */
    public function testAFailingAttendeeIsReportedAsAnErrorLineInTheExcerpt(): void
    {
        $dispatched = [];
        $this->givenTheFirstGenericEmailDispatchFails($dispatched);

        $ids = $this->fixtureAttendeeIds();
        $this->assertGreaterThanOrEqual(2, count($ids), 'fixture must seed at least 2 attendees on the summit');

        $this->service()->send(self::$summit->getId(), [
            'email_flow_event' => GenericSummitAttendeeEmail::EVENT_SLUG,
            'attendees_ids'    => $ids,
        ]);

        $report = EmailExcerpt::getReport();

        $errorLines = array_values(array_filter(
            $report,
            fn($line) => ($line['type'] ?? null) === IEmailExcerptService::ErrorType
        ));
        $this->assertCount(1, $errorLines, 'exactly one ERROR line, for the attendee whose dispatch failed');
        $this->assertStringContainsString(self::SimulatedDispatchFailure, $errorLines[0]['message'], 'the ERROR line must carry the failure reason');

        $emailLines = array_filter(
            $report,
            fn($line) => ($line['type'] ?? null) === IEmailExcerptService::EmailLineType
        );
        $this->assertCount(count($ids) - 1, $emailLines, 'every other attendee must still be reported as sent');
    }
}
