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
 * Class Version20220720202650
 * @package Database\Migrations\Model
 */
final class Version20220720202650 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $builder = new Builder($schema);
        if (!$schema->hasTable("SummitBadgeViewType")) {

            $builder->create('SummitBadgeViewType', function (Table $table) {
                $table->integer("ID", true, false);
                $table->primary("ID");
                $table->string('ClassName')->setDefault("SummitBadgeViewType");
                $table->index("ClassName", "ClassName");
                $table->timestamp('Created');
                $table->timestamp('LastEdited');

                // fields

                $table->string("Name", 255)->setNotnull(true);
                $table->text("Description")->setNotnull(false);
                $table->boolean("IsDefault")->setDefault(false);

                // FK

                $table->integer("SummitID", false, false)->setNotnull(false);
                $table->index("SummitID", "SummitID");
                $table->unique(["SummitID", "Name"], "SummitID_Name");
                $table->foreign("Summit", "SummitID", "ID", ["onDelete" => "CASCADE"], "FK_SummitBadgeViewType_Summit");
            });
        }
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        if ($schema->hasTable("SummitBadgeViewType"))
            $schema->dropTable("SummitBadgeViewType");
    }
}
