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
use LaravelDoctrine\Migrations\Schema\Builder;
use LaravelDoctrine\Migrations\Schema\Table;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260402153110 extends AbstractMigration
{
    const string TableName = "Sponsor_Users";
    public function getDescription(): string
    {
        return 'Add Permissions JSON column to Sponsor_Users table.';
    }

    public function up(Schema $schema): void
    {
        $builder = new Builder($schema);
        if($schema->hasTable(self::TableName) && !$builder->hasColumn(self::TableName, "Permissions")) {
            $builder->table(self::TableName, function (Table $table) {
                $table->json("Permissions")->setNotnull(false)->setDefault(null);
            });
        }
    }

    public function down(Schema $schema): void
    {
        $builder = new Builder($schema);
        if($schema->hasTable(self::TableName) && $builder->hasColumn(self::TableName, "Permissions")) {
            $builder->table(self::TableName, function (Table $table) {
                $table->dropColumn("Permissions");
            });
        }
    }
}
