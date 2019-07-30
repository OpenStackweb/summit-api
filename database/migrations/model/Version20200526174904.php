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
 * Class Version20200526174904
 * @package Database\Migrations\Model
 */
class Version20200526174904 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $builder = new Builder($schema);

        if (!$builder->hasTable("SummitDocument")) {
            $builder->create("SummitDocument", function (Table $table) {
                $table->integer("ID", true, false);
                $table->primary("ID");
                $table->timestamp('Created');
                $table->timestamp('LastEdited');
                $table->string('ClassName');
                $table->string('Name');
                $table->string('Description');
                $table->string('Label');
                $table->integer("FileID", false, false)->setNotnull(false)->setDefault('NULL');
                $table->index("FileID", "FileID");
                $table->foreign("File", "FileID", "ID", ["onDelete" => "CASCADE"]);
                $table->integer("SummitID", false, false)->setNotnull(false)->setDefault('NULL');
                $table->index("SummitID", "SummitID");
                $table->foreign("Summit", "SummitID", "ID", ["onDelete" => "CASCADE"]);
            });
        }

        if (!$builder->hasTable("SummitDocument_EventTypes")) {
            $builder->create("SummitDocument_EventTypes", function (Table $table) {
                $table->integer("ID", true, false);
                $table->primary("ID");

                $table->integer("SummitDocumentID", false, false)->setNotnull(false)->setDefault('NULL');
                $table->index("SummitDocumentID", "SummitDocumentID");

                $table->integer("SummitEventTypeID", false, false)->setNotnull(false)->setDefault('NULL');
                $table->index("SummitEventTypeID", "SummitEventTypeID");

                $table->unique(['SummitDocumentID', 'SummitEventTypeID']);
            });
        }
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $builder = new Builder($schema);

        $builder->dropIfExists("SummitDocument_EventTypes");

        $builder->dropIfExists("SummitDocument");
    }
}
