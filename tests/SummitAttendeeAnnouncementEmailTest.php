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
use LaravelDoctrine\ORM\Facades\Registry;
use models\summit\Summit;
use models\summit\SummitAttendee;
use models\summit\SummitAttendeeAnnouncementEmail;
use models\utils\SilverstripeBaseModel;

/**
 * Covers the SummitAttendeeAnnouncementEmail sent-proof entity: persistence via cascade from
 * SummitAttendee, and the hasAnnouncementEmailTypeSentSince resume-check query it backs.
 *
 * Class SummitAttendeeAnnouncementEmailTest
 */
final class SummitAttendeeAnnouncementEmailTest extends TestCase
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

    /**
     * The bare LaravelDoctrine\ORM\Facades\EntityManager facade resolves the manager registry's
     * default entry, which is 'config' - a different manager from 'model', the one SummitAttendee
     * and its associations live on (SilverstripeBaseModel::EntityManager === 'model'). The fixture
     * itself gets its manager the same way (InsertSummitTestData.php: self::$em =
     * Registry::getManager(SilverstripeBaseModel::EntityManager)), so this mirrors that instead of
     * the facade - using the facade here left every association Doctrine walked from a freshly
     * reloaded attendee looking unmanaged to a UnitOfWork that had never touched it.
     *
     * @return array{0: int, 1: int} [$summit_id, $attendee_id]
     */
    private function fixtureIds(): array
    {
        $attendee = self::$summit->getAttendees()->first();
        $this->assertNotNull($attendee);
        return [self::$summit->getId(), $attendee->getId()];
    }

    /**
     * @return array{0: Summit, 1: SummitAttendee}
     */
    private function reloadFresh(int $summit_id, int $attendee_id): array
    {
        $em = Registry::getManager(SilverstripeBaseModel::EntityManager);
        $em->clear();
        $summit = $em->getRepository(Summit::class)->find($summit_id);
        $attendee = $em->getRepository(SummitAttendee::class)->find($attendee_id);
        return [$summit, $attendee];
    }

    public function testAddAnnouncementEmailPersistsViaCascadeAndSetsAttendeeAndSummit(): void
    {
        [$summit_id, $attendee_id] = $this->fixtureIds();
        [$summit, $attendee] = $this->reloadFresh($summit_id, $attendee_id);
        $em = Registry::getManager(SilverstripeBaseModel::EntityManager);

        $proof = new SummitAttendeeAnnouncementEmail();
        $proof->setType('SUMMIT_REGISTRATION_GENERIC_EMAIL');
        $proof->setSummit($summit);
        $attendee->addAnnouncementEmail($proof);
        $proof->markAsSent();

        $em->persist($proof);
        $em->flush();

        $this->assertNotNull($proof->getId());
        $this->assertTrue($proof->isSent());
        $this->assertSame($attendee_id, $proof->getAttendee()->getId());
        $this->assertSame($summit_id, $proof->getSummit()->getId());
        $this->assertNull($proof->getTicket());
    }

    public function testHasAnnouncementEmailTypeSentSinceMatchesOnAttendeeTypeAndDate(): void
    {
        [$summit_id, $attendee_id] = $this->fixtureIds();
        [$summit, $attendee] = $this->reloadFresh($summit_id, $attendee_id);
        $em = Registry::getManager(SilverstripeBaseModel::EntityManager);

        $type = 'SUMMIT_REGISTRATION_GENERIC_EMAIL';

        $before = new \DateTime('now', new \DateTimeZone('UTC'));
        $before->sub(new \DateInterval('PT1M'));

        $proof = new SummitAttendeeAnnouncementEmail();
        $proof->setType($type);
        $proof->setSummit($summit);
        $attendee->addAnnouncementEmail($proof);
        $proof->markAsSent();

        $em->persist($proof);
        $em->flush();

        $this->assertTrue($attendee->hasAnnouncementEmailTypeSentSince($summit, $type, $before));

        $after = new \DateTime('now', new \DateTimeZone('UTC'));
        $after->add(new \DateInterval('PT1M'));
        $this->assertFalse($attendee->hasAnnouncementEmailTypeSentSince($summit, $type, $after));

        $this->assertFalse($attendee->hasAnnouncementEmailTypeSentSince($summit, 'A_DIFFERENT_TYPE', $before));
    }

    public function testHasAnnouncementEmailTypeSentSinceDistinguishesByTicket(): void
    {
        [$summit_id, $attendee_id] = $this->fixtureIds();
        [$summit, $attendee] = $this->reloadFresh($summit_id, $attendee_id);
        $em = Registry::getManager(SilverstripeBaseModel::EntityManager);
        $ticket = $attendee->getTickets()->first();
        $this->assertNotNull($ticket);

        $type = 'SUMMIT_REGISTRATION_TICKET_EMAIL';
        $before = new \DateTime('now', new \DateTimeZone('UTC'));
        $before->sub(new \DateInterval('PT1M'));

        $proof = new SummitAttendeeAnnouncementEmail();
        $proof->setType($type);
        $proof->setSummit($summit);
        $proof->setTicket($ticket);
        $attendee->addAnnouncementEmail($proof);
        $proof->markAsSent();

        $em->persist($proof);
        $em->flush();

        $this->assertTrue($attendee->hasAnnouncementEmailTypeSentSince($summit, $type, $before, $ticket));
        // the attendee-level (no ticket) check must not match a ticket-scoped proof
        $this->assertFalse($attendee->hasAnnouncementEmailTypeSentSince($summit, $type, $before));
    }
}
