<?php namespace Database\Migrations\Model;
/**
 * Copyright 2022 OpenStack Foundation
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
 * Class Version20220207183947
 * @package Database\Migrations\Model
 */
class Version20220207183947 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $builder = new Builder($schema);

        if(!$schema->hasTable("SummitScheduleConfig")) {
            $builder->create('SummitScheduleConfig', function (Table $table) {
                $table->integer("ID", true, false);
                $table->primary("ID");
                $table->timestamp('Created');
                $table->timestamp('LastEdited');
                $table->string('ClassName')->setNotnull(true);
                // fields
                $table->string("Key")->setNotnull(true)->setDefault("Default");
                $table->string("ColorSource")->setNotnull(true);
                $table->boolean("IsEnabled")->setNotnull(true)->setDefault(true);
                $table->boolean("IsMySchedule")->setNotnull(true)->setDefault(false);
                $table->boolean("OnlyEventsWithAttendeeAccess")->setNotnull(true)->setDefault(false);
                // relations
                $table->integer("SummitID", false, false)->setNotnull(false)->setDefault('NULL');
                $table->index("SummitID", "SummitID");
                $table->foreign("Summit", "SummitID", "ID", ["onDelete" => "CASCADE"]);
                $table->unique(['SummitID','Key'], "Summit_Key");
            });
        }

        if(!$schema->hasTable("SummitScheduleFilterElementConfig")) {
            $builder->create('SummitScheduleFilterElementConfig', function (Table $table) {
                $table->integer("ID", true, false);
                $table->primary("ID");
                $table->timestamp('Created');
                $table->timestamp('LastEdited');
                $table->string('ClassName')->setNotnull(true);
                // fields
                $table->string("Type", 50)->setNotnull(true);
                $table->string("Label", 255)->setNotnull(true);
                $table->boolean("IsEnabled")->setNotnull(true)->setDefault(true);
                $table->text("PrefilterValues")->setNotnull(false)->setDefault(null);
                // relations
                $table->integer("SummitScheduleConfigID", false, false)->setNotnull(false)->setDefault('NULL');
                $table->index("SummitScheduleConfigID", "SummitScheduleConfigID");
                $table->foreign("SummitScheduleConfig", "SummitScheduleConfigID", "ID", ["onDelete" => "CASCADE"]);
                $table->unique(['SummitScheduleConfigID','Type'], "SummitScheduleConfig_Type");
            });
        }
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        $builder = new Builder($schema);
        $builder->dropIfExists("SummitScheduleFilterElementConfig");
        $builder->dropIfExists("SummitScheduleConfig");
    }
}
