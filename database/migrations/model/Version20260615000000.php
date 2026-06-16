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
 * Class Version20260615000000
 * @package Database\Migrations\Model
 *
 * Creates the SummitSponsorshipAddOnType lookup table and adds the nullable
 * AddOnTypeID column to SummitSponsorshipAddOn.
 * A companion migration (Version20260615000001) seeds the four default types,
 * backfills AddOnTypeID from the old Type string, adds the FK, and drops Type.
 */
final class Version20260615000000 extends AbstractMigration
{
    private const AddOnTypeTable = 'SummitSponsorshipAddOnType';
    private const AddOnTable     = 'SummitSponsorshipAddOn';

    public function getDescription(): string
    {
        return 'Create SummitSponsorshipAddOnType table and add AddOnTypeID column to SummitSponsorshipAddOn.';
    }

    public function up(Schema $schema): void
    {
        $builder = new Builder($schema);

        if (!$builder->hasTable(self::AddOnTypeTable)) {
            $builder->create(self::AddOnTypeTable, function (Table $table) {
                $table->integer('ID', true, false);
                $table->primary('ID');

                $table->timestamp('Created')->setNotnull(false);
                $table->timestamp('LastEdited')->setNotnull(false);

                $table->string('Name', 255)->setNotnull(true);
                $table->unique('Name', 'UNIQ_SummitSponsorshipAddOnType_Name');
            });
        }

        if (!$builder->hasColumn(self::AddOnTable, 'AddOnTypeID')) {
            $builder->table(self::AddOnTable, function (Table $table) {
                $table->integer('AddOnTypeID', false, false)->setNotnull(false);
                $table->index('AddOnTypeID', 'IDX_SummitSponsorshipAddOn_AddOnTypeID');
            });
        }
    }

    public function down(Schema $schema): void
    {
        $builder = new Builder($schema);

        // Drop the FK-carrying column before the referenced table.
        // By the time this runs, Version20260615000001 down() will have
        // already dropped the FK constraint.
        if ($builder->hasColumn(self::AddOnTable, 'AddOnTypeID')) {
            $this->addSql('ALTER TABLE `' . self::AddOnTable . '` DROP COLUMN `AddOnTypeID`');
        }

        // Builder: schema diff runs after addSql() — table is dropped last.
        $builder->dropIfExists(self::AddOnTypeTable);
    }
}
