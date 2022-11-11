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
 * Class Version20221111143627
 * @package Database\Migrations\Model
 */
final class Version20221111143627 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $builder = new Builder($schema);
        if (!$builder->hasTable("SelectionPlan_AllowedMembers")) {
            $builder->create("SelectionPlan_AllowedMembers", function (Table $table) {

                $table->integer("ID", true, false);
                $table->primary("ID");

                // FK
                $table->integer("SelectionPlanID", false, false)->setNotnull(false)->setDefault('NULL');
                $table->index("SelectionPlanID", "SelectionPlanID");
                $table->foreign("SelectionPlan", "SelectionPlanID", "ID", ["onDelete" => "CASCADE"], 'FK_SelectionPlan_AllowedMembers_SP');

                $table->integer("MemberID", false, false)->setNotnull(false)->setDefault('NULL');
                $table->index("MemberID", "MemberID");
                $table->foreign("Member", "MemberID", "ID", ["onDelete" => "CASCADE"], 'FK_SelectionPlan_AllowedMembers_M');

                $table->unique(['SelectionPlanID','MemberID']);
            });
        }

    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        $builder = new Builder($schema);
        $builder->dropIfExists('SelectionPlan_AllowedMembers');
    }
}
