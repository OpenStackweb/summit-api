<?php namespace Database\Migrations\Model;
/*
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
 * Per-Activity CFP Reopen: per-presentation, time-boxed submission override.
 *
 * Stores the raw granted duration plus the stamp date; the window end is derived on read
 * (Presentation::getSubmissionReopenedUntil), never stored, so the two cannot drift.
 *
 * All statements are raw addSql() on purpose. Mixing Builder schema-diff with addSql() in one
 * migration reorders them -- addSql() runs first, the diff last (see Version20260615000001) --
 * which would execute the FK before its column exists.
 *
 * Presentation is the JOINED-inheritance child table of SummitEvent, so these columns belong
 * on Presentation, not SummitEvent.
 */
final class Version20260807120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add per-presentation CFP reopen override columns to Presentation.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<SQL
            ALTER TABLE `Presentation`
                ADD COLUMN `SubmissionReopenedHours` INT NULL DEFAULT NULL,
                ADD COLUMN `SubmissionReopenedDate` DATETIME NULL DEFAULT NULL,
                ADD COLUMN `SubmissionReopenedByID` INT NULL DEFAULT NULL
        SQL);

        $this->addSql('CREATE INDEX `SubmissionReopenedByID` ON `Presentation` (`SubmissionReopenedByID`)');

        $this->addSql(<<<SQL
            ALTER TABLE `Presentation`
                ADD CONSTRAINT `FK_Presentation_SubmissionReopenedBy`
                    FOREIGN KEY (`SubmissionReopenedByID`) REFERENCES `Member` (`ID`)
                    ON DELETE SET NULL
        SQL);
    }

    /**
     * NOTE: this is data-destructive. Rolling the migration back drops live grants and the
     * durable actor/date audit trail. Rolling back the *application* alone is safe -- the
     * columns are additive and inert when null -- so prefer that.
     */
    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `Presentation` DROP FOREIGN KEY `FK_Presentation_SubmissionReopenedBy`');
        $this->addSql('DROP INDEX `SubmissionReopenedByID` ON `Presentation`');
        $this->addSql(<<<SQL
            ALTER TABLE `Presentation`
                DROP COLUMN `SubmissionReopenedHours`,
                DROP COLUMN `SubmissionReopenedDate`,
                DROP COLUMN `SubmissionReopenedByID`
        SQL);
    }
}
