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
 * Class Version20220421184854
 * @package Database\Migrations\Model
 */
final class Version20220421184854 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $builder = new Builder($schema);
        if(!$schema->hasTable("SubQuestionRule")) {
            $builder->create('SubQuestionRule', function (Table $table) {
                $table->increments('ID');
                $table->dateTime("Created")->setNotnull(true);
                $table->dateTime("LastEdited")->setNotnull(true);

                $table->string("Visibility")->setNotnull(true)->setDefault("Visible");
                $table->string('VisibilityCondition')->setNotnull(true)->setDefault("Equal");
                $table->text("AnswerValues")->setNotnull(true)->setDefault('');
                $table->string('AnswerValuesOperator')->setNotnull(true)->setDefault('Or');

                $table->integer("ParentQuestionID" );
                $table->index("ParentQuestionID", "ParentQuestionID");
                $table->foreign("ExtraQuestionType", "ParentQuestionID", "ID", ["onDelete" => "CASCADE"], "FK_SubQuestionRule_ParentQuestion");

                $table->integer("SubQuestionID" );
                $table->index("SubQuestionID", "SubQuestionID");
                $table->foreign("ExtraQuestionType", "SubQuestionID", "ID", ["onDelete" => "CASCADE"],"FK_SubQuestionRule_SubQuestion");

                $table->unique(['ParentQuestionID','SubQuestionID']);
            });
        }
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        $schema->dropTable("SubQuestionRule");
    }
}
