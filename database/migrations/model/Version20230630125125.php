<?php namespace Database\Migrations\Model;
/**
 * Copyright 2023 OpenStack Foundation
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
 * Class Version20230630125125
 * @package Database\Migrations\Model
 */
final class Version20230630125125 extends AbstractMigration
{
    /**
     * @param Schema $schema
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function up(Schema $schema): void
    {
        $builder = new Builder($schema);
        if ($schema->hasTable("SelectionPlan")) {
            $builder->table("SelectionPlan", function (Table $table) {
                $table->boolean('AllowTrackChangeRequests')->setDefault(true);
            });
        }
    }

    /**
     * @param Schema $schema
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function down(Schema $schema): void
    {
        $builder = new Builder($schema);
        if ($schema->hasTable("SelectionPlan") && $schema->getTable("SelectionPlan")->hasColumn("AllowTrackChangeRequests")) {
            $builder->table("SelectionPlan", function (Table $table) {
                $table->dropColumn("AllowTrackChangeRequests");
            });
        }
    }
}