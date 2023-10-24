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
 * Class Version20231023174330
 * @package Database\Migrations\Model
 */
final class Version20231023174330 extends AbstractMigration
{
    use CreateTableTrait;

    const TableName = 'SummitAttendeeNote';
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        self::createTable($schema, self::TableName, function(Table $table){
            $table->text("Content")->setNotnull(false);

            // FK
            $table->integer("AuthorID", false, false)->setNotnull(false)->setDefault(null);
            $table->index("AuthorID", "AuthorID");
            $table->foreign("Member", "AuthorID", "ID", ["onDelete" => "CASCADE"]);

            $table->integer("OwnerID", false, false)->setNotnull(true);
            $table->index("OwnerID", "OwnerID");
            $table->foreign("SummitAttendee", "OwnerID", "ID", ["onDelete" => "CASCADE"]);

            $table->integer("TicketID", false, false)->setNotnull(false)->setDefault(null);
            $table->index("TicketID", "TicketID");
            $table->foreign("SummitAttendeeTicket", "TicketID", "ID", ["onDelete" => "CASCADE"]);

            // IDX
            $table->index(['AuthorID', 'OwnerID', 'TicketID'], 'SummitAttendeeNote_IDX_AUTHOR_OWNER_TICKET_ID');
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
