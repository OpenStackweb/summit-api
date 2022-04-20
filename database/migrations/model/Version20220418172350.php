<?php
/**
 * Copyright 2022 OpenStack Foundation
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
namespace Database\Migrations\Model;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema as Schema;
use LaravelDoctrine\Migrations\Schema\Builder;
use LaravelDoctrine\Migrations\Schema\Table;
/**
 * Class Version20220418172350
 * @package Database\Migrations\Model
 */
final class Version20220418172350 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema):void
    {
        $builder = new Builder($schema);
        if(!$schema->hasTable("Summit_RegistrationCompanies")) {
            $builder->create('Summit_RegistrationCompanies', function (Table $table) {
                $table->integer("ID", true, false);
                $table->primary("ID");
                $table->integer("SummitID", false, false)->setNotnull(false);
                $table->index("SummitID", "SummitID");
                $table->foreign("Summit", "SummitID", "ID", ["onDelete" => "CASCADE"], "FK_RegistrationCompanies_Summit");
                $table->integer("CompanyID", false, false)->setNotnull(false);
                $table->index("CompanyID", "CompanyID");
                $table->foreign("Company", "CompanyID", "ID", ["onDelete" => "CASCADE"], "FK_RegistrationCompanies_Company");
                $table->unique(["SummitID", "CompanyID"], "SummitID_CompanyID");
            });
        }
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema):void
    {
        (new Builder($schema))->drop('Summit_RegistrationCompanies');
    }
}
