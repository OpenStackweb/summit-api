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
use LaravelDoctrine\Migrations\Schema\Table;
use LaravelDoctrine\Migrations\Schema\Builder;
/**
 * Class Version20190422151949
 * @package Database\Migrations\Model
 */
final class Version20190422151949 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema):void
    {
        $builder = new Builder($schema);

        if(!$builder->hasTable("SpeakerEditPermissionRequest")) {
            $builder->create('SpeakerEditPermissionRequest', function (Table $table) {
                $table->increments('ID');
                $table->integer("RequestedByID");
                $table->boolean("Approved")->setDefault(false);
                $table->dateTime("ApprovedDate")->setNotnull(false);
                $table->dateTime("Created")->setNotnull(true);
                $table->dateTime("LastEdited")->setNotnull(true);
                $table->text("Hash");
                $table->integer("SpeakerID" );
                $table->index("SpeakerID", "SpeakerID");
                $table->foreign("PresentationSpeaker", "SpeakerID", "ID", ["onDelete" => "CASCADE"]);
                $table->foreign("Member", "RequestedByID", "ID", ["onDelete" => "CASCADE"]);
            });
        }
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema):void
    {
        $builder = new Builder($schema);


        if($builder->hasTable("SpeakerEditPermissionRequest"))
            $builder->drop('SpeakerEditPermissionRequest');
    }
}
