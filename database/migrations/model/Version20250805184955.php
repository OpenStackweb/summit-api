<?php
/**
 * Copyright 2025 OpenStack Foundation
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
declare(strict_types=1);

namespace Database\Migrations\Model;

use App\Models\Foundation\Summit\Events\RSVP\RSVPInvitation;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use LaravelDoctrine\Migrations\Schema\Builder;
use LaravelDoctrine\Migrations\Schema\Table;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250805184955 extends AbstractMigration
{
    const TableName = "RSVPInvitation";

    public function getDescription(): string
    {
        return "Create Table ".self::TableName;
    }

    public function up(Schema $schema): void
    {
        $builder = new Builder($schema);

        if (!$builder->hasTable(self::TableName)) {
            $builder->create(self::TableName, function (Table $table) {

                $table->integer('ID', true, false);
                $table->primary('ID');
                $table->timestamp('Created');
                $table->timestamp('LastEdited');
                $table->string('ClassName')->setDefault('RSVPInvitation');

                $table->string('Status')->setNotnull(true)->setLength(255)->setDefault(RSVPInvitation::Status_Pending);
                $table->string("Hash", 255)->setNotnull(false);
                $table->unique("Hash", "Hash");
                $table->timestamp("ActionDate")->setNotnull(false)->setDefault(null);
                $table->timestamp("SentDate")->setNotnull(false)->setDefault(null);

                // FK
                $table->integer("SummitEventID", false, false)->setNotnull(false)->setDefault('NULL');
                $table->index("SummitEventID", "SummitEventID");
                $table->foreign("SummitEvent", "SummitEventID", "ID", ["onDelete" => "CASCADE"], 'FK_RSVPInvitation_SummitEvent');

                $table->integer("AttendeeID", false, false)->setNotnull(false)->setDefault('NULL');
                $table->index("AttendeeID", "AttendeeID");
                $table->foreign("SummitAttendee", "AttendeeID", "ID", ["onDelete" => "CASCADE"], 'FK_RSVPInvitation_SummitAttendee');

                $table->unique(['SummitEventID', 'AttendeeID'], "IDX_RSVPInvitation_EventID_AttendeeID");
            });
        }

    }

    public function down(Schema $schema): void
    {
        $builder = new Builder($schema);
        $builder->dropIfExists(self::TableName);
    }
}
