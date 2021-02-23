<?php namespace Database\Migrations\Model;
/**
 * Copyright 2019 OpenStack Foundation
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
 * Class Version20210326171114
 * @package Database\Migrations\Model
 */
class Version20210326171114 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema):void
    {
        $builder = new Builder($schema);

        if(!$schema->hasTable("PresentationActionType")) {
            $builder->create('PresentationActionType', function (Table $table) {

                $table->integer("ID", true, false);
                $table->primary("ID");

                $table->timestamp('Created');
                $table->timestamp('LastEdited');
                $table->string('ClassName')->setDefault("PresentationActionType");

                $table->string('Label')->setNotnull(true)->setLength(255);
                $table->integer('Order')->setDefault(1);

                // FK
                $table->integer("SummitID", false, false)->setNotnull(false)->setDefault('NULL');
                $table->index("SummitID", "SummitID");
                $table->foreign("Summit", "SummitID", "ID", ["onDelete" => "CASCADE"]);

                $table->unique(["SummitID", "Label"]);
            });
        }

        if(!$schema->hasTable("PresentationAction")) {
            $builder->create('PresentationAction', function (Table $table) {

                $table->integer("ID", true, false);
                $table->primary("ID");

                $table->timestamp('Created');
                $table->timestamp('LastEdited');
                $table->string('ClassName')->setDefault("PresentationAction");

                $table->boolean('IsCompleted')->setDefault(false);

                // FK
                $table->integer("TypeID", false, false)->setNotnull(false)->setDefault('NULL');
                $table->index("TypeID", "TypeID");
                $table->foreign("PresentationActionType", "TypeID", "ID", ["onDelete" => "CASCADE"]);

                $table->integer("PresentationID", false, false)->setNotnull(false)->setDefault('NULL');
                $table->index("PresentationID", "PresentationID");
                $table->foreign("Presentation", "PresentationID", "ID", ["onDelete" => "CASCADE"]);

                $table->integer("CreatedByID", false, false)->setNotnull(false)->setDefault('NULL');
                $table->index("CreatedByID", "CreatedByID");
                $table->foreign("Member", "CreatedByID", "ID", ["onDelete" => "SET NULL"]);

                $table->integer("UpdateByID", false, false)->setNotnull(false)->setDefault('NULL');
                $table->index("UpdateByID", "UpdateByID");
                $table->foreign("Member", "UpdateByID", "ID", ["onDelete" => "SET NULL"]);

                $table->unique(["PresentationID", "TypeID"]);
            });
        }
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema):void
    {

    }
}
