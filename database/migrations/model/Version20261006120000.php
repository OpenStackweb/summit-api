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
 * Class Version20261006120000
 * Append-only attendee check-in/check-out log (see adr/005-attendee-check-in-audit-log.md).
 * @package Database\Migrations\Model
 */
final class Version20261006120000 extends AbstractMigration
{
    use CreateTableTrait;

    const TableName = 'SummitAttendeeCheckInLog';

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        self::createTable($schema, self::TableName, function (Table $table) {
            $table->string("Action")->setNotnull(true);
            $table->string("Source")->setNotnull(true);
            $table->string("ClientID")->setNotnull(false)->setDefault(null);
            $table->text("Reason")->setNotnull(false);
            $table->string("IpAddress", 45)->setNotnull(false)->setDefault(null);
            $table->string("UserAgent", 512)->setNotnull(false)->setDefault(null);

            // FK
            $table->integer("AttendeeID", false, false)->setNotnull(true);
            $table->foreign("SummitAttendee", "AttendeeID", "ID", ["onDelete" => "CASCADE"]);
            $table->index(["AttendeeID", "Created"], "AttendeeID_Created");

            // FK
            $table->integer("ActorMemberID", false, false)->setNotnull(false)->setDefault(null);
            $table->index("ActorMemberID", "ActorMemberID");
            $table->foreign("Member", "ActorMemberID", "ID", ["onDelete" => "SET NULL"]);
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
