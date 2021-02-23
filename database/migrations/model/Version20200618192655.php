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
 * Class Version20200618192655
 * @package Database\Migrations\Model
 */
class Version20200618192655 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema):void
    {
        $builder = new Builder($schema);

        if(!$schema->hasTable("SummitAdministratorPermissionGroup")) {
            $builder->create('SummitAdministratorPermissionGroup', function (Table $table) {

                $table->integer("ID", true, false);
                $table->primary("ID");

                $table->timestamp('Created');
                $table->timestamp('LastEdited');
                $table->string('ClassName');
                $table->string('Title')->setNotnull(true);
                $table->unique(['Title']);
            });
        }

        if(!$schema->hasTable("SummitAdministratorPermissionGroup_Members")) {
            $builder->create('SummitAdministratorPermissionGroup_Members', function (Table $table) {

                $table->integer("ID", true, false);
                $table->primary("ID");

                // FK
                $table->integer("MemberID", false, false)->setNotnull(false)->setDefault('NULL');
                $table->index("MemberID", "MemberID");
                //$table->foreign("Member", "MemberID", "ID", ["onDelete" => "CASCADE"]);

                $table->integer("SummitAdministratorPermissionGroupID", false, false)->setNotnull(false)->setDefault('NULL');
                $table->index("SummitAdministratorPermissionGroupID", "SummitAdministratorPermissionGroupID");
                //$table->foreign("Summit", "SummitID", "ID", ["onDelete" => "CASCADE"]);

                $table->unique(['SummitAdministratorPermissionGroupID', 'MemberID']);
            });
        }

        if(!$schema->hasTable("SummitAdministratorPermissionGroup_Summits")) {
            $builder->create('SummitAdministratorPermissionGroup_Summits', function (Table $table) {

                $table->integer("ID", true, false);
                $table->primary("ID");

                // FK
                $table->integer("SummitID", false, false)->setNotnull(false)->setDefault('NULL');
                $table->index("SummitID", "SummitID");
                //$table->foreign("Member", "MemberID", "ID", ["onDelete" => "CASCADE"]);

                $table->integer("SummitAdministratorPermissionGroupID", false, false)->setNotnull(false)->setDefault('NULL');
                $table->index("SummitAdministratorPermissionGroupID", "SummitAdministratorPermissionGroupID");
                //$table->foreign("Summit", "SummitID", "ID", ["onDelete" => "CASCADE"]);

                $table->unique(['SummitAdministratorPermissionGroupID', 'SummitID']);
            });
        }
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema):void
    {

        $builder = new Builder($schema);
        $builder->dropIfExists("SummitAdministratorPermissionGroup_Members");
        $builder->dropIfExists("SummitAdministratorPermissionGroup_Summits");
        $builder->dropIfExists("SummitAdministratorPermissionGroup");
    }
}
