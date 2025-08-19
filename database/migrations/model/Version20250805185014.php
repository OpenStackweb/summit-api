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
use models\summit\RSVP;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250805185014 extends AbstractMigration
{


    public function getDescription(): string
    {
        return 'Add new fields to RSVP table';
    }

    public function up(Schema $schema): void
    {
        $builder = new Builder($schema);
        if($schema->hasTable("RSVP") && !$builder->hasColumn("RSVP", "ActionDate")) {
            $builder->table("RSVP", function (Table $table) {
                $table->timestamp("ActionDate")->setNotnull(false)->setDefault(null);
                $table->string('Status')->setNotnull(true)->setLength(255)->setDefault(RSVP::Status_Active);
            });
        }
    }

    public function down(Schema $schema): void
    {
        $builder = new Builder($schema);
        if($schema->hasTable("RSVP") && $builder->hasColumn("RSVP", "ActionDate")) {
            $builder->table("RSVP", function (Table $table) {
                $table->dropColumn("ActionDate");
                $table->dropColumn("Status");
            });
        }
    }
}
