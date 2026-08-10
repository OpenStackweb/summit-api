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
use LaravelDoctrine\Migrations\Schema\Builder;
use LaravelDoctrine\Migrations\Schema\Table;

/**
 * Per-Activity CFP Reopen: per-presentation, time-boxed submission override. Columns and index.
 *
 * Stores the raw granted duration plus the stamp date; the window end is derived on read
 * (Presentation::getSubmissionReopenedUntil), never stored, so the two cannot drift.
 *
 * The foreign key is deliberately NOT here; it lives in Version20260807120001, mirroring
 * Version2026061500000{0,1}. Within one migration every addSql() statement runs before the
 * Builder schema diff, so a migration doing both would execute the FK before its column existed.
 * Splitting is the repo's answer to that ordering hazard, and it keeps this half entirely on
 * Builder, where the existence guards live.
 *
 * Every component is guarded on ITSELF. MySQL DDL does not roll back and the statements commit
 * separately, so a run interrupted between the columns and the index leaves the columns in place
 * with the migration unrecorded; guarding on one column alone would skip the missing index on
 * retry. Re-running repairs instead.
 *
 * Presentation is the JOINED-inheritance child table of SummitEvent, so these columns belong
 * on Presentation, not SummitEvent.
 */
final class Version20260807120000 extends AbstractMigration
{
    private const TableName   = 'Presentation';
    private const HoursColumn = 'SubmissionReopenedHours';
    private const DateColumn  = 'SubmissionReopenedDate';
    private const ActorColumn = 'SubmissionReopenedByID';

    public function getDescription(): string
    {
        return 'Add per-presentation CFP reopen override columns and index to Presentation.';
    }

    public function up(Schema $schema): void
    {
        $builder = new Builder($schema);
        $current = $schema->getTable(self::TableName);

        $needs_hours = !$current->hasColumn(self::HoursColumn);
        $needs_date  = !$current->hasColumn(self::DateColumn);
        $needs_actor = !$current->hasColumn(self::ActorColumn);
        $needs_index = !$current->hasIndex(self::ActorColumn);

        if (!$needs_hours && !$needs_date && !$needs_actor && !$needs_index) return;

        $builder->table(
            self::TableName,
            function (Table $table) use ($needs_hours, $needs_date, $needs_actor, $needs_index) {
                if ($needs_hours) $table->integer(self::HoursColumn, false, false)->setNotnull(false);
                if ($needs_date) $table->dateTime(self::DateColumn)->setNotnull(false);
                if ($needs_actor) $table->integer(self::ActorColumn, false, false)->setNotnull(false);

                // explicit rather than left to the FK: this is the index MySQL would create for
                // the constraint anyway, and naming it keeps it recognizable in SHOW INDEX
                if ($needs_index) $table->index(self::ActorColumn, self::ActorColumn);
            }
        );
    }

    /**
     * NOTE: this is data-destructive. Rolling the migration back drops live grants and the
     * durable actor/date audit trail. Rolling back the *application* alone is safe -- the
     * columns are additive and inert when null -- so prefer that.
     *
     * Version20260807120001::down() has already dropped the foreign key by the time this runs;
     * MySQL would refuse to drop the index underneath a live constraint.
     */
    public function down(Schema $schema): void
    {
        // addSql() only, so the drop order (index, then columns) is the order written.
        $this->addSql('DROP INDEX `' . self::ActorColumn . '` ON `' . self::TableName . '`');
        $this->addSql(<<<SQL
            ALTER TABLE `Presentation`
                DROP COLUMN `SubmissionReopenedHours`,
                DROP COLUMN `SubmissionReopenedDate`,
                DROP COLUMN `SubmissionReopenedByID`
        SQL);
    }
}
