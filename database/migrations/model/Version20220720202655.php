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
 * Class Version20220720202655
 * @package Database\Migrations\Model
 */
final class Version20220720202655 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $builder = new Builder($schema);

        if (!$schema->hasTable("SummitBadgeViewType_SummitBadgeType")) {

            $builder->create('SummitBadgeViewType_SummitBadgeType', function (Table $table) {
                $table->integer("ID", true, false);
                $table->primary("ID");
                $table->string('ClassName')->setDefault("SummitBadgeViewType");
                $table->index("ClassName", "ClassName");

                // FK

                $table->integer("SummitBadgeViewTypeID", false, false)->setNotnull(false);
                $table->index("SummitBadgeViewTypeID", "SummitBadgeViewTypeID");
                $table->foreign("SummitBadgeViewType", "SummitBadgeViewTypeID", "ID", ["onDelete" => "CASCADE"], "FK_SummitBadgeViewType_SummitBadgeType_SummitBadgeViewType");

                $table->integer("SummitBadgeTypeID", false, false)->setNotnull(false);
                $table->index("SummitBadgeTypeID", "SummitBadgeTypeID");
                $table->foreign("SummitBadgeType", "SummitBadgeTypeID", "ID", ["onDelete" => "CASCADE"], "FK_SummitBadgeViewType_SummitBadgeType_SummitBadgeType");

                $table->unique(["SummitBadgeViewTypeID", "SummitBadgeTypeID"],'IDX_SummitBadgeViewTypeID_SummitBadgeTypeID');

            });
        }

    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        if ($schema->hasTable("SummitBadgeViewType_SummitBadgeType")) {
            $schema->dropTable("SummitBadgeViewType_SummitBadgeType");
        }
    }
}
