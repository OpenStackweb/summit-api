<?php namespace Database\Migrations\Model;
/**
 * Copyright 2021 OpenStack Foundation
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
 * Class Version20210521135639
 * @package Database\Migrations\Model
 */
class Version20210521135639 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema):void
    {
        $builder = new Builder($schema);

        if(!$schema->hasTable("ExtraQuestionType")) {
            $builder->create('ExtraQuestionType', function (Table $table) {

                $table->integer("ID", true, false);
                $table->primary("ID");

                $table->timestamp('Created');
                $table->timestamp('LastEdited');
                $table->string('ClassName')->setNotnull(true);

                $table->string('Name')->setNotnull(true);
                $table->string('Type')->setNotnull(true);
                $table->string('Label')->setNotnull(true);
                $table->integer('Order')->setNotnull(true)->setDefault(1);
                $table->boolean('Mandatory')->setNotnull(true)->setDefault(false);
                $table->string('Placeholder')->setNotnull(false)->setDefault('');
            });
        }

        if(!$schema->hasTable("SummitSelectionPlanExtraQuestionType")) {
            $builder->create('SummitSelectionPlanExtraQuestionType', function (Table $table) {

                $table->integer("ID", true, false);
                $table->primary("ID");

                // FK

                $table->integer("SelectionPlanID", false, false)->setNotnull(false)->setDefault('NULL');
                $table->index("SelectionPlanID", "SelectionPlanID");
                $table->foreign("SelectionPlan", "SelectionPlanID", "ID", ["onDelete" => "CASCADE"]);

            });
        }


        if(!$schema->hasTable("ExtraQuestionTypeValue")) {
            $builder->create('ExtraQuestionTypeValue', function (Table $table) {

                $table->integer("ID", true, false);
                $table->primary("ID");

                $table->timestamp('Created');
                $table->timestamp('LastEdited');
                $table->string('ClassName')->setNotnull(true);

                $table->string('Label')->setNotnull(true);
                $table->string('Value')->setNotnull(true);
                $table->integer('Order')->setNotnull(true)->setDefault(1);

                // FK

                $table->integer("QuestionID", false, false)->setNotnull(false)->setDefault('NULL');
                $table->index("QuestionID", "QuestionID");
                $table->foreign("ExtraQuestionType", "QuestionID", "ID", ["onDelete" => "CASCADE"]);

            });
        }

        if(!$schema->hasTable("ExtraQuestionAnswer")) {
            $builder->create('ExtraQuestionAnswer', function (Table $table) {

                $table->integer("ID", true, false);
                $table->primary("ID");

                $table->timestamp('Created');
                $table->timestamp('LastEdited');
                $table->string('ClassName')->setNotnull(true);

                $table->string('Value')->setNotnull(true);

                // FK

                $table->integer("QuestionID", false, false)->setNotnull(false)->setDefault('NULL');
                $table->index("QuestionID", "QuestionID");
                $table->foreign("ExtraQuestionType", "QuestionID", "ID", ["onDelete" => "CASCADE"]);

            });
        }

        if(!$schema->hasTable("PresentationExtraQuestionAnswer")) {
            $builder->create('PresentationExtraQuestionAnswer', function (Table $table) {

                $table->integer("ID", true, false);
                $table->primary("ID");

                // FK

                $table->integer("PresentationID", false, false)->setNotnull(false)->setDefault('NULL');
                $table->index("PresentationID", "PresentationID");
                $table->foreign("Presentation", "PresentationID", "ID", ["onDelete" => "CASCADE"]);

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
