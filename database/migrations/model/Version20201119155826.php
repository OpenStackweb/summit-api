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
 * Class Version20201119155826
 * @package Database\Migrations\Model
 */
class Version20201119155826 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema):void
    {
        $builder = new Builder($schema);

        if(!$schema->hasTable("SponsoredProject")) {
            $builder->create('SponsoredProject', function (Table $table) {

                $table->integer("ID", true, false);
                $table->primary("ID");

                $table->timestamp('Created');
                $table->timestamp('LastEdited');
                $table->string('ClassName');

                $table->string('Name')->setLength(255);
                $table->string('Description')->setNotnull(false)->setLength(1024);
                $table->string('Slug')->setLength(255);
                $table->boolean('IsActive');
                $table->unique("Name");
                $table->unique("Slug");
            });
        }

        if(!$schema->hasTable("ProjectSponsorshipType")) {
            $builder->create('ProjectSponsorshipType', function (Table $table) {

                $table->integer("ID", true, false);
                $table->primary("ID");

                $table->timestamp('Created');
                $table->timestamp('LastEdited');
                $table->string('ClassName');

                $table->string('Name')->setLength(255);
                $table->string('Description')->setNotnull(false)->setLength(1024);
                $table->string('Slug')->setLength(255);
                $table->integer('Order')->setDefault(1);;
                $table->boolean('IsActive');
                // FK
                $table->integer("SponsoredProjectID", false, false)->setNotnull(false)->setDefault('NULL');
                $table->index("SponsoredProjectID", "SponsoredProjectID");
                $table->foreign("SponsoredProject", "SponsoredProjectID", "ID", ["onDelete" => "CASCADE"]);
                $table->unique(["Name", "SponsoredProjectID"]);
                $table->unique(["Slug", "SponsoredProjectID"]);
            });
        }

        if(!$schema->hasTable("SupportingCompany")) {
            $builder->create('SupportingCompany', function (Table $table) {

                $table->integer("ID", true, false);
                $table->primary("ID");

                $table->integer('Order')->setDefault(1);

                // FK
                $table->integer("CompanyID", false, false)->setNotnull(false)->setDefault('NULL');
                $table->index("CompanyID", "CompanyID");
                $table->foreign("Company", "CompanyID", "ID", ["onDelete" => "CASCADE"]);

                // FK
                $table->integer("ProjectSponsorshipTypeID", false, false)->setNotnull(false)->setDefault('NULL');
                $table->index("ProjectSponsorshipTypeID", "ProjectSponsorshipTypeID");
                $table->foreign("ProjectSponsorshipType", "ProjectSponsorshipTypeID", "ID", ["onDelete" => "CASCADE"]);
            });
        }
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema):void
    {
        $builder = new Builder($schema);

        $builder->dropIfExists("SupportingCompany");

        $builder->dropIfExists("ProjectSponsorshipType");

        $builder->dropIfExists("SponsoredProject");
    }
}
