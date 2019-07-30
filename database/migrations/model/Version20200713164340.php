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
 * Class Version20200713164340
 * @package Database\Migrations\Model
 */
class Version20200713164340 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $builder = new Builder($schema);

        // these types are global and seeded
        if(!$schema->hasTable("SummitMediaFileType")) {
            $builder->create('SummitMediaFileType', function (Table $table) {

                $table->integer("ID", true, false);
                $table->primary("ID");

                $table->timestamp('Created');
                $table->timestamp('LastEdited');
                $table->string('ClassName');

                $table->string('Name');
                $table->string('Description')->setNotnull(false);
                $table->boolean("IsSystemDefine");
                $table->string('AllowedExtensions');

                $table->unique(['Name']);
            });
        }

        if(!$schema->hasTable("SummitMediaUploadType")) {
            $builder->create('SummitMediaUploadType', function (Table $table) {

                $table->integer("ID", true, false);
                $table->primary("ID");

                $table->timestamp('Created');
                $table->timestamp('LastEdited');
                $table->string('ClassName');

                $table->string('Name');
                $table->string('Description')->setNotnull(false);
                $table->integer("MaxSize")->setDefault(1024);
                $table->boolean("IsMandatory")->setDefault(false);
                $table->string('PrivateStorageType');
                $table->string('PublicStorageType');

                // FK

                $table->integer("SummitID", false, false)->setNotnull(false)->setDefault('NULL');
                $table->index("SummitID", "SummitID");
                $table->foreign("Summit", "SummitID", "ID", ["onDelete" => "CASCADE"]);

                $table->integer("TypeID", false, false)->setNotnull(false)->setDefault('NULL');
                $table->index("TypeID", "TypeID");
                $table->foreign("SummitMediaFileType", "TypeID", "ID", ["onDelete" => "CASCADE"]);

                $table->index(['SummitID']);
                $table->index(['TypeID']);
                $table->unique(['SummitID', 'Name']);
            });
        }

        if(!$schema->hasTable("PresentationType_SummitMediaUploadType")) {
            $builder->create('PresentationType_SummitMediaUploadType', function (Table $table) {

                $table->integer("ID", true, false);
                $table->primary("ID");

                $table->integer("PresentationTypeID", false, false)->setNotnull(false)->setDefault('NULL');
                $table->index("PresentationTypeID", "PresentationTypeID");
                //$table->foreign("PresentationType", "PresentationTypeID", "ID", ["onDelete" => "CASCADE"]);

                $table->integer("SummitMediaUploadTypeID", false, false)->setNotnull(false)->setDefault('NULL');
                $table->index("SummitMediaUploadTypeID", "SummitMediaUploadTypeID");
                //$table->foreign("SummitMediaUploadType", "SummitMediaUploadTypeID", "ID", ["onDelete" => "CASCADE"]);

                $table->unique(['PresentationTypeID', 'SummitMediaUploadTypeID']);
            });
        }

        if(!$schema->hasTable("PresentationMediaUpload")) {
            $builder->create('PresentationMediaUpload', function (Table $table) {
                $table->integer("ID", true, false);
                $table->primary("ID");
                $table->string('FileName');

                $table->integer("SummitMediaUploadTypeID", false, false)->setNotnull(false)->setDefault('NULL');
                $table->index("SummitMediaUploadTypeID", "SummitMediaUploadTypeID");
                $table->foreign("SummitMediaUploadType", "SummitMediaUploadTypeID", "ID", ["onDelete" => "CASCADE"]);

            });
        }
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $builder = new Builder($schema);
        $builder->dropIfExists('PresentationMediaUpload');
        $builder->dropIfExists('PresentationType_SummitMediaUploadType');
        $builder->dropIfExists('SummitMediaUploadType');
        $builder->dropIfExists('SummitMediaFileType');
    }
}
