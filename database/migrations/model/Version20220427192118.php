<?php namespace Database\Migrations\Model;
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
use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema as Schema;
use LaravelDoctrine\Migrations\Schema\Builder;
use LaravelDoctrine\Migrations\Schema\Table;
/**
 * Class Version20220427192118
 * @package Database\Migrations\Model
 */
final class Version20220427192118 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $builder = new Builder($schema);
        if($schema->hasTable("SummitDocument") && !$builder->hasColumn("SummitDocument", "SelectionPlanID")) {
            $builder->table('SummitDocument', function (Table $table) {
                $table->integer('SelectionPlanID')->setNotnull(false)->setDefault(null);
                $table->index("SelectionPlanID", "IDX_SummitDocument_SelectionPlanID");
                $table->foreign("SelectionPlan", "SelectionPlanID", "ID", ["onDelete" => "SET NULL"], 'FK_SummitDocument_SelectionPlan');
            });
        }
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        $builder = new Builder($schema);
        if($schema->hasTable("SummitDocument") && $builder->hasColumn("SummitDocument", "SelectionPlanID")) {
            $builder->table('SummitDocument', function (Table $table) {
                $table->dropColumn('SelectionPlanID');
            });
        }
    }
}
