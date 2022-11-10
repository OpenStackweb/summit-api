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
 * Class Version20221031174714
 * @package Database\Migrations\Model
 */
final class Version20221031174714 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $builder = new Builder($schema);
        if ($builder->hasTable("SummitSelectionPlanExtraQuestionType") && !$builder->hasColumn("SummitSelectionPlanExtraQuestionType", "SummitID")) {
            $builder->table("SummitSelectionPlanExtraQuestionType", function (Table $table) {
                $table->integer("SummitID", false, false)->setNotnull(false)->setDefault('NULL');
                $table->index("SummitID", "SummitID");
                $table->foreign("Summit", "SummitID", "ID", ["onDelete" => "CASCADE"], "FK_SummitSelectionPlanExtraQuestionType_Summit");
            });
        }
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        $builder = new Builder($schema);
        if ($builder->hasTable("SummitSelectionPlanExtraQuestionType") && $builder->hasColumn("SummitSelectionPlanExtraQuestionType", "SummitID")) {
            $builder->table("SummitSelectionPlanExtraQuestionType", function (Table $table) {
                $table->dropColumn("SummitID");
            });
        }
    }
}
