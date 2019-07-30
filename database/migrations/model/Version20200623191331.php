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
 * Class Version20200623191331
 * @package Database\Migrations\Model
 */
class Version20200623191331 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $builder = new Builder($schema);

        if(!$schema->hasTable("SummitEventAttendanceMetric")) {
            $builder->create('SummitEventAttendanceMetric', function (Table $table) {

                $table->integer("ID", true, false);
                $table->primary("ID");

                $table->timestamp('Created');
                $table->timestamp('LastEdited');
                $table->string('ClassName');

                $table->timestamp('IngressDate')->setNotnull(false);
                $table->timestamp('OutgressDate')->setNotnull(false);
                // FK

                $table->integer("MemberID", false, false)->setNotnull(false)->setDefault('NULL');
                $table->index("MemberID", "MemberID");
                $table->foreign("Member", "MemberID", "ID", ["onDelete" => "CASCADE"]);

                $table->integer("SummitEventID", false, false)->setNotnull(false)->setDefault('NULL');
                $table->index("SummitEventID", "SummitEventID");
                $table->foreign("SummitEvent", "SummitEventID", "ID", ["onDelete" => "CASCADE"]);

                $table->index(['MemberID', 'SummitEventID' ]);
            });
        }
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $builder = new Builder($schema);

        $builder->dropIfExists('SummitEventAttendanceMetric');
    }
}
