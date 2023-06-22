<?php namespace Database\Migrations\Model;
/**
 * Copyright 2023 OpenStack Foundation
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
 * Class Version20230616161141
 * @package Database\Migrations\Model
 */
final class Version20230616161141 extends AbstractMigration
{
    const TableName = 'SummitProposedScheduleLock';

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {

        $builder = new Builder($schema);
        if (!$schema->hasTable(self::TableName)) {
            $builder->create(self::TableName, function (Table $table) {
                $table->integer("ID", true,true);
                $table->primary("ID");

                $table->timestamp('Created');
                $table->timestamp('LastEdited');

                // Fields

                $table->string('Reason', 1024)->setNotnull(false)->setDefault('');

                // FK

                $table->integer("SummitProposedScheduleID", false, true)->setNotnull(true);
                $table->index("SummitProposedScheduleID", "SummitProposedScheduleID");
                $table->foreign("SummitProposedSchedule", "SummitProposedScheduleID", "ID", ["onDelete" => "CASCADE"], "FK_SummitProposedScheduleLock_SummitProposedSchedule");

                $table->integer("TrackID", false, false)->setNotnull(true);
                $table->index("TrackID", "TrackID");
                $table->foreign("PresentationCategory", "TrackID", "ID", ["onDelete" => "CASCADE"], "FK_SummitProposedScheduleLock_Track");

                $table->integer("CreatedByID", false, false)->setNotnull(true);
                $table->index("CreatedByID", "CreatedByID");
                $table->foreign("SummitTrackChair", "CreatedByID", "ID", ["onDelete" => "CASCADE"], "FK_SummitProposedScheduleLock_TrackChair");

                $table->unique(['SummitProposedScheduleID', 'TrackID']);
            });
        }
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        $schema->dropTable(self::TableName);
    }
}
