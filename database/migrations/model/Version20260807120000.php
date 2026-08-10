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
 * Per-Activity CFP Reopen: per-presentation, time-boxed submission override.
 *
 * Stores the raw granted duration plus the stamp date; the window end is derived on read
 * (Presentation::getSubmissionReopenedUntil), never stored, so the two cannot drift.
 *
 * up() is entirely Builder and down() is entirely addSql(); neither MIXES the two. That matters:
 * within one migration, addSql() statements all run before the Builder schema diff (see
 * Version20260615000001), so a mixed up() would execute the FK before its column existed.
 * Staying on one mechanism per direction removes the ordering question instead of managing it.
 *
 * Presentation is the JOINED-inheritance child table of SummitEvent, so these columns belong
 * on Presentation, not SummitEvent.
 */
final class Version20260807120000 extends AbstractMigration
{
    private const TableName   = 'Presentation';
    private const ActorColumn = 'SubmissionReopenedByID';
    private const FkName      = 'FK_Presentation_SubmissionReopenedBy';

    public function getDescription(): string
    {
        return 'Add per-presentation CFP reopen override columns to Presentation.';
    }

    public function up(Schema $schema): void
    {
        $builder = new Builder($schema);
        $current = $schema->getTable(self::TableName);

        // Each component is guarded on ITSELF, not on the column. DDL does not roll back, so a
        // run interrupted between the ADD COLUMN and the ADD CONSTRAINT leaves the columns in
        // place with the migration unrecorded; a column-only guard would then skip straight past
        // the missing FK and record the migration as complete. Re-running instead repairs.
        $needs_hours = !$current->hasColumn('SubmissionReopenedHours');
        $needs_date  = !$current->hasColumn('SubmissionReopenedDate');
        $needs_actor = !$current->hasColumn(self::ActorColumn);
        $needs_index = !$current->hasIndex(self::ActorColumn);
        $needs_fk    = !$current->hasForeignKey(self::FkName);

        if (!$needs_hours && !$needs_date && !$needs_actor && !$needs_index && !$needs_fk) return;

        $builder->table(
            self::TableName,
            function (Table $table) use ($needs_hours, $needs_date, $needs_actor, $needs_index, $needs_fk) {
                if ($needs_hours) $table->integer('SubmissionReopenedHours', false, false)->setNotnull(false);
                if ($needs_date) $table->dateTime('SubmissionReopenedDate')->setNotnull(false);
                if ($needs_actor) $table->integer(self::ActorColumn, false, false)->setNotnull(false);

                if ($needs_index) $table->index(self::ActorColumn, self::ActorColumn);
                if ($needs_fk) {
                    $table->foreign(
                        'Member',
                        self::ActorColumn,
                        'ID',
                        ['onDelete' => 'SET NULL'],
                        self::FkName
                    );
                }
            }
        );
    }

    /**
     * NOTE: this is data-destructive. Rolling the migration back drops live grants and the
     * durable actor/date audit trail. Rolling back the *application* alone is safe -- the
     * columns are additive and inert when null -- so prefer that.
     */
    public function down(Schema $schema): void
    {
        // addSql() only, so the drop order (FK, then index, then columns) is the order written.
        $this->addSql('ALTER TABLE `Presentation` DROP FOREIGN KEY `' . self::FkName . '`');
        $this->addSql('DROP INDEX `' . self::ActorColumn . '` ON `Presentation`');
        $this->addSql(<<<SQL
            ALTER TABLE `Presentation`
                DROP COLUMN `SubmissionReopenedHours`,
                DROP COLUMN `SubmissionReopenedDate`,
                DROP COLUMN `SubmissionReopenedByID`
        SQL);
    }
}
