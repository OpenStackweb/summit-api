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
use LaravelDoctrine\Migrations\Schema\Builder;
use LaravelDoctrine\Migrations\Schema\Table;
/**
 * Class Version20200512132942
 * @package Database\Migrations\Model
 */
class Version20200512132942 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {

        $builder = new Builder($schema);

        if (!$builder->hasTable("SummitEmailFlowType")) {
            $builder->create("SummitEmailFlowType", function (Table $table) {

                $table->integer("ID", true, false);
                $table->primary("ID");
                $table->timestamp('Created');
                $table->timestamp('LastEdited');
                $table->string('ClassName');
                $table->string('Name');

            });

            $builder->create("SummitEmailEventFlowType", function (Table $table) {

                $table->integer("ID", true, false);
                $table->primary("ID");
                $table->timestamp('Created');
                $table->timestamp('LastEdited');
                $table->string('ClassName');
                $table->string('Slug');
                $table->string('Name');
                $table->string('DefaultEmailTemplateIdentifier');
                $table->integer("SummitEmailFlowTypeID", false, false)->setNotnull(false)->setDefault('NULL');
                $table->index("SummitEmailFlowTypeID", "SummitEmailFlowTypeID");
                $table->foreign("SummitEmailFlowType", "SummitEmailFlowTypeID", "ID", ["onDelete" => "CASCADE"]);
            });

            $builder->create("SummitEmailEventFlow", function (Table $table) {

                $table->integer("ID", true, false);
                $table->primary("ID");
                $table->timestamp('Created');
                $table->timestamp('LastEdited');
                $table->string('ClassName');
                $table->string('EmailTemplateIdentifier');
                $table->integer("SummitEmailEventFlowTypeID", false, false)->setNotnull(false)->setDefault('NULL');
                $table->index("SummitEmailEventFlowTypeID", "SummitEmailEventFlowTypeID");
                $table->foreign("SummitEmailEventFlowType", "SummitEmailEventFlowTypeID", "ID", ["onDelete" => "CASCADE"]);
                $table->integer("SummitID", false, false)->setNotnull(false)->setDefault('NULL');
                $table->index("SummitID", "SummitID");
                $table->foreign("Summit", "SummitID", "ID", ["onDelete" => "CASCADE"]);
            });
        }
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $builder = new Builder($schema);

        $builder->dropIfExists("SummitEmailEventFlow");

        $builder->dropIfExists("SummitEmailEventFlowType");

        $builder->dropIfExists("SummitEmailFlowType");
    }
}
