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
use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema as Schema;
use LaravelDoctrine\Migrations\Schema\Table;
/**
 * Class Version20260908190443
 * @package Database\Migrations\Model
 */
final class Version20260908190443 extends AbstractMigration
{
    use CreateTableTrait;

    const TableName = 'SummitAttendeeAnnouncementEmail';
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        self::createTable($schema, self::TableName, function(Table $table){
            $table->string("AnnouncementEmailTypeSent")->setNotnull(true);
            $table->timestamp("AnnouncementEmailSentDate")->setNotnull(true);

            // FK
            $table->integer("AttendeeID", false, false)->setNotnull(true);
            $table->index("AttendeeID", "AttendeeID");
            $table->foreign("SummitAttendee", "AttendeeID", "ID", ["onDelete" => "CASCADE"]);

            // FK
            $table->integer("TicketID", false, false)->setNotnull(false)->setDefault(null);
            $table->index("TicketID", "TicketID");
            $table->foreign("SummitAttendeeTicket", "TicketID", "ID", ["onDelete" => "CASCADE"]);

            // FK
            $table->integer("SummitID", false, false)->setNotnull(true);
            $table->index("SummitID", "SummitID");
            $table->foreign("Summit", "SummitID", "ID", ["onDelete" => "CASCADE"]);

            $table->index(["AttendeeID", "AnnouncementEmailTypeSent"], "AttendeeID_AnnouncementEmailTypeSent");
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
