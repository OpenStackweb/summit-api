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

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema as Schema;
use LaravelDoctrine\Migrations\Schema\Builder;
use LaravelDoctrine\Migrations\Schema\Table;

/**
 * Class Version20260615000001
 * @package Database\Migrations\Model
 *
 * Companion to Version20260615000000.
 * Seeds the four default SummitSponsorshipAddOnType rows, backfills
 * AddOnTypeID on every existing SummitSponsorshipAddOn row, adds the FK
 * constraint, and drops the legacy Type string column.
 *
 * Execution order within up():
 *   addSql() calls run first (INSERT → UPDATE → ADD FK),
 *   then schema diff from Builder runs (DROP COLUMN Type).
 * This guarantees the UPDATE that reads Type completes before the column is removed.
 */
final class Version20260615000001 extends AbstractMigration
{
    private const AddOnTypeTable = 'SummitSponsorshipAddOnType';
    private const AddOnTable     = 'SummitSponsorshipAddOn';
    private const FkName         = 'FK_SummitSponsorshipAddOn_AddOnType';

    public function getDescription(): string
    {
        return 'Seed SummitSponsorshipAddOnType defaults, backfill AddOnTypeID, add FK, drop legacy Type column.';
    }

    public function up(Schema $schema): void
    {
        $builder = new Builder($schema);

        if (!$builder->hasTable(self::AddOnTypeTable)) {
            // Upstream DDL migration did not run — skip rather than crash.
            return;
        }

        // Seed the four types that correspond to the old string constants.
        // INSERT IGNORE makes this safe to re-run (unique constraint on Name).
        $this->addSql(<<<SQL
            INSERT IGNORE INTO `SummitSponsorshipAddOnType` (`Created`, `LastEdited`, `Name`) VALUES
                (NOW(), NOW(), 'Booth'),
                (NOW(), NOW(), 'Meeting_Room'),
                (NOW(), NOW(), 'Schedule_Spot'),
                (NOW(), NOW(), 'Signage_Spot')
        SQL);

        // Backfill AddOnTypeID from the still-present Type string column.
        $this->addSql(<<<SQL
            UPDATE `SummitSponsorshipAddOn` a
                INNER JOIN `SummitSponsorshipAddOnType` t ON t.`Name` = a.`Type`
                SET a.`AddOnTypeID` = t.`ID`
        SQL);

        // Add the FK constraint after the data is consistent.
        $this->addSql(<<<SQL
            ALTER TABLE `SummitSponsorshipAddOn`
                ADD CONSTRAINT `FK_SummitSponsorshipAddOn_AddOnType`
                    FOREIGN KEY (`AddOnTypeID`) REFERENCES `SummitSponsorshipAddOnType` (`ID`)
                    ON DELETE SET NULL
        SQL);

        // Builder: DROP COLUMN runs as schema diff, i.e. after all addSql() above,
        // so the UPDATE that reads Type has already executed.
        if ($builder->hasColumn(self::AddOnTable, 'Type')) {
            $builder->table(self::AddOnTable, function (Table $table) {
                $table->dropColumn('Type');
            });
        }
    }

    public function down(Schema $schema): void
    {
        $builder = new Builder($schema);

        // Restore the Type column so the backfill UPDATE below has somewhere to write.
        if (!$builder->hasColumn(self::AddOnTable, 'Type')) {
            $this->addSql(
                'ALTER TABLE `SummitSponsorshipAddOn` ADD COLUMN `Type` VARCHAR(255) CHARACTER SET utf8 NULL DEFAULT NULL'
            );
        }

        // Backfill Type from AddOnTypeID while the lookup table still exists.
        $this->addSql(<<<SQL
            UPDATE `SummitSponsorshipAddOn` a
                INNER JOIN `SummitSponsorshipAddOnType` t ON t.`ID` = a.`AddOnTypeID`
                SET a.`Type` = t.`Name`
        SQL);

        // Drop the FK so Version20260615000000 down() can remove the column and table.
        $this->addSql(
            'ALTER TABLE `SummitSponsorshipAddOn` DROP FOREIGN KEY `' . self::FkName . '`'
        );
    }
}
