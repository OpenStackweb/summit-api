<?php namespace Database\Migrations\Model;
/**
 * Copyright 2024 OpenStack Foundation
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
 * Class Version20240513153432
 * @package Database\Migrations\Model
 */
class Version20240513153432 extends AbstractMigration
{
    /**
     * @param Schema $schema
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function up(Schema $schema):void
    {
        $builder = new Builder($schema);
        if($builder->hasTable("SummitScheduleConfig") && !$builder->hasColumns("SummitScheduleConfig", ["TimeFormat"])) {
            $builder->table("SummitScheduleConfig", function (Table $table) {
                $table->string("TimeFormat",3)->setNotnull(true);
            });
        }
    }

    /**
     * @param Schema $schema
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function down(Schema $schema):void
    {
        $builder = new Builder($schema);
        if($builder->hasTable("SummitScheduleConfig") && $builder->hasColumns("SummitScheduleConfig", ["TimeFormat"])) {
            $builder->table("SummitScheduleConfig", function (Table $table) {
                $table->dropColumn("TimeFormat");
            });
        }
    }
}
