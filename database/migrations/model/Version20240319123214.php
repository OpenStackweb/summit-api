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
/**
 * Class Version20240319123214
 * @package Database\Migrations\Model
 */
final class Version20240319123214 extends AbstractMigration
{
    use CreateTableTrait;

    const TableName = 'SummitLeadReportSetting';
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        self::createTable($schema, self::TableName, function(Table $table){
            $table->json("Columns")->setNotnull(true);

            // FK
            $table->integer("SummitID", false, false)->setNotnull(true);
            $table->index("SummitID", "SummitID");
            $table->foreign("Summit", "SummitID", "ID", ["onDelete" => "CASCADE"]);

            // FK
            $table->integer("SponsorID", false, false)->setNotnull(false)->setDefault(null);
            $table->index("SponsorID", "SponsorID");
            $table->foreign("Sponsor", "SponsorID", "ID", ["onDelete" => "CASCADE"]);

            $table->unique(['SummitID', 'SponsorID']);
        });
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        $schema->dropTable(self::TableName);
    }
}
