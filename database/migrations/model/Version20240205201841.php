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
 * Class Version20240205201841
 * @package Database\Migrations\Model
 */
final class Version20240205201841 extends AbstractMigration
{

    use CreateTableTrait;

    const TableName = 'SummitEvent_SummitTicketType';
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        self::createTable($schema, self::TableName, function(Table $table){

            // FK
            $table->integer("SummitEventID", false, false)->setNotnull(false)->setDefault(null);
            $table->index("SummitEventID", "SummitEventID");
            $table->foreign("SummitEvent", "SummitEventID", "ID", ["onDelete" => "CASCADE"]);

            // FK
            $table->integer("SummitTicketTypeID", false, false)->setNotnull(false)->setDefault(null);
            $table->index("SummitTicketTypeID", "SummitTicketTypeID");
            $table->foreign("SummitTicketType", "SummitTicketTypeID", "ID", ["onDelete" => "CASCADE"]);

            // IDX
            $table->unique(['SummitEventID','SummitTicketTypeID'], 'IDX_SummitEvent_SummitTicketType');
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
