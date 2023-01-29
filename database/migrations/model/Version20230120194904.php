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
 * Class Version20230120194904
 * @package Database\Migrations\Model
 */
final class Version20230120194904 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $builder = new Builder($schema);
        if (!$schema->hasTable("SummitProposedSchedule")) {

            $builder->create('SummitProposedSchedule', function (Table $table) {
                $table->integer("ID", true, true);
                $table->primary("ID");
                $table->string('ClassName')->setDefault("SummitProposedSchedule");
                $table->index("ClassName", "ClassName");
                $table->timestamp('Created');
                $table->timestamp('LastEdited');

                // Fields
                $table->string("Name")->setNotnull(false)->setDefault('NULL');
                $table->string("Source")->setNotnull(true)->setDefault("TrackChairs");

                // FK

                $table->integer("SummitID", false, false)->setNotnull(false)->setDefault('NULL');
                $table->index("SummitID", "SummitID");
                $table->foreign("Summit", "SummitID", "ID", ["onDelete" => "CASCADE"], "FK_SummitProposedSchedule_Summit");

                $table->integer("CreatedByID", false, false)->setNotnull(false)->setDefault('NULL');
                $table->index("CreatedByID", "CreatedByID");
                $table->foreign("Member", "CreatedByID", "ID", ["onDelete" => "CASCADE"], "FK_SummitProposedSchedule_CreatedBy");

            });
        }
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        $schema->dropTable("SummitProposedSchedule");
    }
}
