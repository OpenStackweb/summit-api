<?php namespace Database\Migrations\Model;
/**
 * Copyright 2020 OpenStack Foundation
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
use LaravelDoctrine\Migrations\Schema\Table;
use LaravelDoctrine\Migrations\Schema\Builder;
/**
 * Class Version20200110184019
 * @package Database\Migrations\Model
 */

/**
 * Class Version20200123133515
 * @package Database\Migrations\Model
 */
class Version20200123133515 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $builder = new Builder($schema);

        if ($builder->hasTable("SummitAttendeeBadge") && $builder->hasColumn("SummitAttendeeBadge", "PrintedTimes")) {
            $builder->table("SummitAttendeeBadge", function (Table $table) {
                $table->dropColumn("PrintedTimes");
                $table->dropColumn("PrintDate");
            });
        }

        if (!$builder->hasTable("SummitAttendeeBadgePrint")) {
            $builder->create("SummitAttendeeBadgePrint", function (Table $table) {

                $table->integer("ID", true, false);
                $table->primary("ID");
                $table->timestamp('Created');
                $table->timestamp('LastEdited');

                $table->timestamp('PrintDate')->setNotnull(false);

                $table->integer("BadgeID", false, false)->setNotnull(false)->setDefault('NULL');
                $table->index("BadgeID", "BadgeID");
                $table->foreign("SummitAttendeeBadge", "BadgeID", "ID", ["onDelete" => "CASCADE"]);

                $table->integer("RequestorID", false, false)->setNotnull(false)->setDefault('NULL');
                $table->index("RequestorID", "RequestorID");
                $table->foreign("Member", "RequestorID", "ID", ["onDelete" => "CASCADE"]);

            });
        }

        if (!$builder->hasTable("SummitAttendeeBadgePrintRule")) {
            $builder->create("SummitAttendeeBadgePrintRule", function (Table $table) {

                $table->integer("ID", true, false);
                $table->primary("ID");
                $table->timestamp('Created');
                $table->timestamp('LastEdited');

                $table->integer('MaxPrintTimes',false, false)->setNotnull(true)->setDefault(0);

                $table->integer("GroupID", false, false)->setNotnull(false)->setDefault('NULL');
                $table->index("GroupID", "GroupID");
                $table->foreign("Group", "GroupID", "ID", ["onDelete" => "CASCADE"]);

            });
        }
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $builder = new Builder($schema);

        if ($builder->hasTable("SummitAttendeeBadge") && !$builder->hasColumn("SummitAttendeeBadge", "PrintedTimes")) {
            $builder->table("SummitAttendeeBadge", function (Table $table) {
                $table->timestamp('PrintDate')->setNotnull(false);
                $table->integer("PrintedTimes")->setNotnull(false)->setDefault(0);
            });
        }

        $builder->dropIfExists("SummitAttendeeBadgePrint");

        $builder->dropIfExists("SummitAttendeeBadgePrintRule");
    }
}
