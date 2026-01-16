<?php namespace Database\Migrations\Model;
/**
 * Copyright 2026 OpenStack Foundation
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
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use LaravelDoctrine\Migrations\Schema\Builder;
use LaravelDoctrine\Migrations\Schema\Table;
/**
 * Class Version20260115181251
 * @package Database\Migrations\Model
 */
final class Version20260115181251 extends AbstractMigration
{
    use CreateTableTrait;

    const TableName = 'CustomerCaseStudy';

    public function getDescription(): string
    {
        return 'Create CustomerCaseStudy table';
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
         self::createTable($schema, self::TableName, function(Table $table){
            $table->string("Name")->setLength(250)->setNotnull(true);
            $table->string("Uri")->setLength(250)->setNotnull(false);
            $table->integer("`Order`" )->setNotnull(true)->setDefault(1);

            // FK
            $table->integer("OwnerID", false, false)->setNotnull(true);
            $table->index("OwnerID", "OwnerID");
            $table->foreign("CompanyService", "OwnerID", "ID", ["onDelete" => "CASCADE"]);

            $table->unique(['Name', 'OwnerID']);
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
