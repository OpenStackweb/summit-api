<?php namespace Database\Migrations\Model;
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

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Class Version20260429120000
 * @package Database\Migrations\Model
 *
 * Approach D / D-half-2: adds 4 composite indexes that make the registration-stats
 * queries fast after the D-half-1 SQL rewrite removes the SummitOrder join and filters
 * on SummitAttendeeTicket.SummitID directly.
 *
 * Production deployment note:
 *   MySQL 8+ runs ADD INDEX with ALGORITHM=INPLACE, LOCK=NONE by default (non-unique indexes).
 *   For pre-MySQL-8 production, run via pt-online-schema-change or gh-ost instead.
 */
final class Version20260429120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Approach D: add composite indexes for registration-stats performance (IDX_SummitAttendeeTicket_Stats, IDX_SummitAttendeeTicket_BoughtDate, IDX_SummitAttendee_HallCheckIn, IDX_SummitAttendee_VirtualCheckIn)';
    }

    public function up(Schema $schema): void
    {
        $ticketIndexes = $this->sm->listTableIndexes('SummitAttendeeTicket');
        $attendeeIndexes = $this->sm->listTableIndexes('SummitAttendee');

        if (!array_key_exists('IDX_SummitAttendeeTicket_Stats', $ticketIndexes)) {
            $this->addSql(
                'ALTER TABLE SummitAttendeeTicket ADD INDEX IDX_SummitAttendeeTicket_Stats (SummitID, Status, IsActive)'
            );
        }

        if (!array_key_exists('IDX_SummitAttendeeTicket_BoughtDate', $ticketIndexes)) {
            $this->addSql(
                'ALTER TABLE SummitAttendeeTicket ADD INDEX IDX_SummitAttendeeTicket_BoughtDate (SummitID, Status, IsActive, TicketBoughtDate)'
            );
        }

        if (!array_key_exists('IDX_SummitAttendee_HallCheckIn', $attendeeIndexes)) {
            $this->addSql(
                'ALTER TABLE SummitAttendee ADD INDEX IDX_SummitAttendee_HallCheckIn (SummitID, SummitHallCheckedIn, SummitHallCheckedInDate)'
            );
        }

        if (!array_key_exists('IDX_SummitAttendee_VirtualCheckIn', $attendeeIndexes)) {
            $this->addSql(
                'ALTER TABLE SummitAttendee ADD INDEX IDX_SummitAttendee_VirtualCheckIn (SummitID, SummitVirtualCheckedInDate)'
            );
        }
    }

    public function down(Schema $schema): void
    {
        $ticketIndexes = $this->sm->listTableIndexes('SummitAttendeeTicket');
        $attendeeIndexes = $this->sm->listTableIndexes('SummitAttendee');

        if (array_key_exists('IDX_SummitAttendeeTicket_Stats', $ticketIndexes)) {
            $this->addSql('ALTER TABLE SummitAttendeeTicket DROP INDEX IDX_SummitAttendeeTicket_Stats');
        }

        if (array_key_exists('IDX_SummitAttendeeTicket_BoughtDate', $ticketIndexes)) {
            $this->addSql('ALTER TABLE SummitAttendeeTicket DROP INDEX IDX_SummitAttendeeTicket_BoughtDate');
        }

        if (array_key_exists('IDX_SummitAttendee_HallCheckIn', $attendeeIndexes)) {
            $this->addSql('ALTER TABLE SummitAttendee DROP INDEX IDX_SummitAttendee_HallCheckIn');
        }

        if (array_key_exists('IDX_SummitAttendee_VirtualCheckIn', $attendeeIndexes)) {
            $this->addSql('ALTER TABLE SummitAttendee DROP INDEX IDX_SummitAttendee_VirtualCheckIn');
        }
    }
}
