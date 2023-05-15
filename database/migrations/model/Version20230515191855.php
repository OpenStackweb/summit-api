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
use LaravelDoctrine\Migrations\Schema\Table;
use LaravelDoctrine\Migrations\Schema\Builder;

/**
 * Class Version20230515191855
 * @package Database\Migrations\Model
 */
final class Version20230515191855 extends AbstractMigration
{
    const TableName = 'SummitProposedScheduleAllowedDay';

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {

        $builder = new Builder($schema);
        if (!$schema->hasTable("")) {
            $builder->create(self::TableName, function (Table $table) {
                $table->integer("ID", true,true);
                $table->primary("ID");
                $table->string('ClassName')->setDefault(self::TableName);
                $table->index("ClassName", "ClassName");
                $table->timestamp('Created');
                $table->timestamp('LastEdited');

                // Fields

                $table->string("`Day`", 8)->setNotnull(true);
                $table->smallInteger("`From`",)->setNotnull(false)->setDefault('NULL');
                $table->smallInteger("`To`", 8)->setNotnull(false)->setDefault('NULL');

                // FK

                $table->integer("AllowedLocationID", false, false)->setNotnull(false)->setDefault('NULL');
                $table->index("AllowedLocationID", "AllowedLocationID");
                $table->foreign("ProposedScheduleAllowedLocation", "AllowedLocationID", "ID", ["onDelete" => "CASCADE"], "FK_SummitSign_Location");

                $table->unique(["Day", "AllowedLocationID"], "IDX_UNIQUE_ProposedScheduleAllowedDay_Day_Location");

            });
        }
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        $schema->dropTable(self::TableName);
    }
}
