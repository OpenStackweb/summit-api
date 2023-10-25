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
 * Class Version20231025125509
 * @package Database\Migrations\Model
 */
class Version20231025125509 extends AbstractMigration
{
    const TableName = "SummitAttendeeBadgePrintBackUp";

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema):void
    {
        $builder = new Builder($schema);

        if (!$builder->hasTable(self::TableName)) {
            $builder->create(self::TableName, function (Table $table) {

                $table->integer('ID', false, false);
                $table->primary('ID');
                $table->timestamp('Created');
                $table->timestamp('LastEdited');
                $table->timestamp('PrintDate')->setNotnull(false);
                $table->integer('BadgeID', false, false)->setNotnull(false)->setDefault('NULL');
                $table->integer('RequestorID', false, false)->setNotnull(false)->setDefault('NULL');
                $table->string('ClassName')->setDefault('SummitAttendeeBadgePrint');
                $table->integer('SummitBadgeViewTypeID', false, false)->setNotnull(false);
            });
        }
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema):void
    {
        $builder = new Builder($schema);

        $builder->dropIfExists(self::TableName);
    }
}
