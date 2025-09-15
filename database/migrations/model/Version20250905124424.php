<?php namespace Database\Migrations\Model;

/**
 * Copyright 2025 OpenStack Foundation
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
final class Version20250905124424 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add Metadata to AuditLog table';
    }

    public function up(Schema $schema): void
    {
        $builder = new Builder($schema);
        if($schema->hasTable("AuditLog") && !$builder->hasColumn("AuditLog", "Metadata")) {
            $builder->table("AuditLog", function (Table $table) {
                $table->string('Metadata')->setNotnull(false)->setLength(255)->setDefault(null);
            });
        }
    }

    public function down(Schema $schema): void
    {
        $builder = new Builder($schema);
        if($schema->hasTable("AuditLog") && $builder->hasColumn("AuditLog", "Metadata")) {
            $builder->table("AuditLog", function (Table $table) {
                $table->dropColumn('Metadata');
            });
        }
    }
}
