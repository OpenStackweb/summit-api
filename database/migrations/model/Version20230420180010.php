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
use LaravelDoctrine\Migrations\Schema\Builder;
use LaravelDoctrine\Migrations\Schema\Table;

/**
 * Class Version20230420180010
 * @package Database\Migrations\Model
 */
final class Version20230420180010 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $builder = new Builder($schema);
        if (!$schema->hasTable("SummitSign")) {
            $builder->create("SummitSign", function (Table $table) {

                $table->bigInteger("ID", true, true);
                $table->primary("ID");
                $table->timestamp("Created")->setDefault('CURRENT_TIMESTAMP');
                $table->timestamp("LastEdited")->setDefault('CURRENT_TIMESTAMP');
                // fields

                $table->text("Template")->setNotnull(false)->setDefault('');
                // FK
                $table->integer("SummitID", false, false)->setNotnull(false)->setDefault('NULL');
                $table->index("SummitID", "SummitID");
                $table->foreign("Summit", "SummitID", "ID", ["onDelete" => "CASCADE"], "FK_SummitSign_Summit");

                $table->integer("LocationID", false, false)->setNotnull(false)->setDefault('NULL');
                $table->index("LocationID", "LocationID");
                $table->foreign("SummitAbstractLocation", "LocationID", "ID", ["onDelete" => "CASCADE"], "FK_SummitSign_Location");


                $table->unique(["SummitID", "LocationID"], "IDX_UNIQUE_SummitSign_Summit_Location");
            });
        }
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        if ($schema->hasTable("SummitSign")) {
            $schema->dropTable("SummitSign");
        }
    }
}
