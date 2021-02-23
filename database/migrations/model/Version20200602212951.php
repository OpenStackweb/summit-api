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
 * Class Version20200602212951
 * @package Database\Migrations\Model
 */
class Version20200602212951 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema):void
    {
        $builder = new Builder($schema);

        if (!$builder->hasTable("SummitRegistrationInvitation")) {

            $builder->create("SummitRegistrationInvitation", function (Table $table) {

                $table->integer("ID", true, false);
                $table->primary("ID");
                $table->timestamp('Created');
                $table->timestamp('LastEdited');
                $table->string('ClassName');

                $table->string("Hash", 255)->setNotnull(false);
                $table->unique("Hash", "Hash");
                $table->timestamp("AcceptedDate")->setNotnull(false);
                $table->string('Email')->setLength(255)->setNotnull(true);
                $table->string('FirstName')->setLength(100)->setNotnull(true);
                $table->string('LastName')->setLength(100)->setNotnull(true);
                $table->string('SetPasswordLink')->setLength(255)->setNotnull(false);
                // FK

                $table->integer("MemberID", false, false)->setNotnull(false)->setDefault('NULL');
                $table->index("MemberID", "MemberID");
                $table->foreign("Member", "MemberID", "ID", ["onDelete" => "CASCADE"]);

                $table->integer("SummitID", false, false)->setNotnull(false)->setDefault('NULL');
                $table->index("SummitID", "SummitID");
                $table->foreign("Summit", "SummitID", "ID", ["onDelete" => "CASCADE"]);

                $table->integer("SummitOrderID", false, false)->setNotnull(false)->setDefault('NULL');
                $table->index("SummitOrderID", "SummitOrderID");
                $table->foreign("SummitOrder", "SummitOrderID", "ID", ["onDelete" => "CASCADE"]);

                // Index

                $table->unique(["Email", "SummitID"]);
            });
        }
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema):void
    {
        $builder = new Builder($schema);

        $builder->dropIfExists("SummitRegistrationInvitation");

    }
}
