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
 * Class Version20191202223721
 * @package Database\Migrations\Model
 */
class Version20191202223721 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $builder = new Builder($schema);

        if ($builder->hasTable("SummitAttendee") && !$builder->hasColumn("SummitAttendee", "Company")) {
            $builder->table("SummitAttendee", function (Table $table) {
                $table->string("Company", 255)->setNotnull(false);
                $table->index("Company", "Company");
                $table->integer("CompanyID", false, false)->setNotnull(false)->setDefault('NULL');
                $table->index("CompanyID", "CompanyID");
                $table->foreign("Company", "CompanyID", "ID", ["onDelete" => "CASCADE"]);
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
