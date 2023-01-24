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
 * Class Version20230120200108
 * @package Database\Migrations\Model
 */
final class Version20230120200108 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $builder = new Builder($schema);
        if (!$schema->hasTable("SummitProposedScheduleSummitEvent")) {

            $builder->create('SummitProposedScheduleSummitEvent', function (Table $table) {

                $table->integer("ID", true, true);
                $table->primary("ID");
                $table->string('ClassName')->setDefault("SummitProposedScheduleSummitEvent");
                $table->index("ClassName", "ClassName");
                $table->timestamp('Created');
                $table->timestamp('LastEdited');

                // Fields

                $table->timestamp("StartDate")->setNotnull(true);
                $table->timestamp("EndDate")->setNotnull(true);
                $table->integer("Duration")->setNotnull(true)->setDefault(0);

                // FK

                $table->integer("ScheduleID", false, true)->setNotnull(true);
                $table->index("ScheduleID", "ScheduleID");
                $table->foreign("SummitProposedSchedule", "ScheduleID", "ID", ["onDelete" => "CASCADE"], "FK_SummitProposedScheduleSummitEvent_Schedule");

                $table->integer("SummitEventID", false, true)->setNotnull(true);
                $table->index("SummitEventID", "SummitEventID");
                $table->foreign("SummitEvent", "SummitEventID", "ID", ["onDelete" => "CASCADE"], "FK_SummitProposedScheduleSummitEvent_Event");
                $table->unique(['ScheduleID','SummitEventID'],'IDX_SummitProposedScheduleSummitEvent_Event_Unique');

                $table->integer("LocationID", false, true)->setNotnull(true);
                $table->index("LocationID", "LocationID");
                $table->foreign("SummitAbstractLocation", "LocationID", "ID", ["onDelete" => "CASCADE"], "FK_SummitProposedScheduleSummitEvent_Location");

                $table->integer("CreatedByID", false, false)->setNotnull(false)->setDefault('NULL');
                $table->index("CreatedByID", "CreatedByID");
                $table->foreign("Member", "CreatedByID", "ID", ["onDelete" => "CASCADE"], "FK_SummitProposedScheduleSummitEvent_CreatedBy");

                $table->integer("UpdatedByID", false, false)->setNotnull(false)->setDefault('NULL');
                $table->index("UpdatedByID", "UpdatedByID");
                $table->foreign("Member", "UpdatedByID", "ID", ["onDelete" => "CASCADE"], "FK_SummitProposedScheduleSummitEvent_UpdatedBy");

            });
        }
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        $schema->dropTable("SummitProposedScheduleSummitEvent");
    }
}
