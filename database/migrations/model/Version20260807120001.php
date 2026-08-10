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
 * Per-Activity CFP Reopen: the foreign key from Presentation.SubmissionReopenedByID to Member.
 *
 * Separated from Version20260807120000 on purpose, mirroring Version2026061500000{0,1}: within one
 * migration every addSql() statement runs before the Builder schema diff, so a migration that both
 * added the column through Builder and added this constraint through addSql() would execute the
 * constraint first, against a column that did not exist yet. Splitting removes the ordering
 * question rather than managing it, and leaves this migration as pure addSql().
 *
 * The schema is only READ here, never mutated, so no schema diff is produced and the ordering
 * hazard cannot reappear. The read is what makes the migration re-runnable after a partial
 * failure, matching the guards in Version20260807120000.
 *
 * ON DELETE SET NULL: a deleted member must not take the presentation's grant with it. The grant
 * then reads as ownerless rather than cascading, which is why closeNow() logs "no active grant"
 * rather than naming member 0.
 */
final class Version20260807120001 extends AbstractMigration
{
    private const TableName   = 'Presentation';
    private const ActorColumn = 'SubmissionReopenedByID';
    private const FkName      = 'FK_Presentation_SubmissionReopenedBy';

    public function getDescription(): string
    {
        return 'Add the Presentation.SubmissionReopenedByID foreign key to Member.';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->getTable(self::TableName);

        // the column is Version20260807120000's job; if it is absent that migration has not run,
        // and adding the constraint here would fail on a missing column rather than explain itself
        if (!$table->hasColumn(self::ActorColumn)) return;
        if ($table->hasForeignKey(self::FkName)) return;

        $this->addSql(<<<SQL
            ALTER TABLE `Presentation`
                ADD CONSTRAINT `FK_Presentation_SubmissionReopenedBy`
                    FOREIGN KEY (`SubmissionReopenedByID`) REFERENCES `Member` (`ID`)
                    ON DELETE SET NULL
        SQL);
    }

    /**
     * Runs BEFORE Version20260807120000::down(), which drops the index this constraint sits on.
     */
    public function down(Schema $schema): void
    {
        $table = $schema->getTable(self::TableName);
        if (!$table->hasForeignKey(self::FkName)) return;

        $this->addSql('ALTER TABLE `' . self::TableName . '` DROP FOREIGN KEY `' . self::FkName . '`');
    }
}
