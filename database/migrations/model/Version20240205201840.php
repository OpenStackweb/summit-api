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

/**
 * Class Version20240205201840
 * @package Database\Migrations\Model
 */
final class Version20240205201840 extends AbstractMigration
{
    use CreateTableTrait;

    const TableName = 'SummitEventType_SummitTicketType';
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        self::createTable($schema, self::TableName, function(Table $table){

              // FK
            $table->integer("SummitEventTypeID", false, false)->setNotnull(false)->setDefault(null);
            $table->index("SummitEventTypeID", "SummitEventTypeID");
            $table->foreign("SummitEventType", "SummitEventTypeID", "ID", ["onDelete" => "CASCADE"]);

            // FK
            $table->integer("SummitTicketTypeID", false, false)->setNotnull(false)->setDefault(null);
            $table->index("SummitTicketTypeID", "SummitTicketTypeID");
            $table->foreign("SummitTicketType", "SummitTicketTypeID", "ID", ["onDelete" => "CASCADE"]);

            // IDX
            $table->unique(['SummitEventTypeID', 'SummitTicketTypeID'], 'IDX_SummitEventType_SummitTicketType');
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
