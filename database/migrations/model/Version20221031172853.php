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
 * Class Version20221031172853
 * @package Database\Migrations\Model
 */
final class Version20221031172853 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $builder = new Builder($schema);
        if (!$schema->hasTable("SummitSelectionPlanExtraQuestionType_SelectionPlan")) {
            $builder->create('SummitSelectionPlanExtraQuestionType_SelectionPlan', function (Table $table) {

                $table->bigInteger("ID", true, false);
                $table->primary("ID");
                $table->smallInteger('CustomOrder')->setNotnull(true)->setDefault(1);

                $table->integer("SummitSelectionPlanExtraQuestionTypeID", false, false)->setNotnull(false)->setDefault('NULL');
                $table->index("SummitSelectionPlanExtraQuestionTypeID", "SummitSelectionPlanExtraQuestionTypeID");
                $table->foreign("SummitSelectionPlanExtraQuestionType", "SummitSelectionPlanExtraQuestionTypeID", "ID", ["onDelete" => "CASCADE"], "FK_AssignedSelectionPlan_Question_Type");

                $table->integer("SelectionPlanID", false, false)->setNotnull(false)->setDefault('NULL');
                $table->index("SelectionPlanID", "SelectionPlanID");
                $table->foreign("SelectionPlan", "SelectionPlanID", "ID", ["onDelete" => "CASCADE"], "FK_AssignedSelectionPlan_SelectionPlan");
                $table->unique(['SummitSelectionPlanExtraQuestionTypeID', 'SelectionPlanID']);

            });
        }
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        $builder = new Builder($schema);
        $builder->dropIfExists("SummitSelectionPlanExtraQuestionType_SelectionPlan");
    }
}
