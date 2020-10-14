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
 * Class Version20201014155708
 * @package Database\Migrations\Model
 */
class Version20201014155708 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $builder = new Builder($schema);

        if(!$schema->hasTable("SummitMetric")) {
            $builder->create('SummitMetric', function (Table $table) {

                $table->integer("ID", true, false);
                $table->primary("ID");

                $table->timestamp('Created');
                $table->timestamp('LastEdited');
                $table->string('ClassName');
                $table->string('Type')->setNotnull(true)->setDefault('GENERAL');
                $table->string('Ip')->setNotnull(false);
                $table->string('Origin')->setNotnull(false);
                $table->string('Browser')->setNotnull(false);

                $table->timestamp('IngressDate')->setNotnull(false);
                $table->timestamp('OutgressDate')->setNotnull(false);
                // FK
                $table->integer("MemberID", false, false)->setNotnull(false)->setDefault('NULL');
                $table->index("MemberID", "MemberID");
                $table->foreign("Member", "MemberID", "ID", ["onDelete" => "CASCADE"]);

                // FK
                $table->integer("SummitID", false, false)->setNotnull(false)->setDefault('NULL');
                $table->index("SummitID", "SummitID");
                $table->foreign("Summit", "SummitID", "ID", ["onDelete" => "CASCADE"]);
            });
        }

        if(!$schema->hasTable("SummitSponsorMetric")) {
            $builder->create('SummitSponsorMetric', function (Table $table) {

                $table->integer("ID", true, false);
                $table->primary("ID");
                $table->string('ClassName');

                // FK
                $table->integer("SponsorID", false, false)->setNotnull(false)->setDefault('NULL');
                $table->index("SponsorID", "SponsorID");
                $table->foreign("Sponsor", "SponsorID", "ID", ["onDelete" => "CASCADE"]);
            });
        }
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {

    }
}
