<?php namespace Database\Migrations\Model;
/**
 * Copyright 2020 OpenStack Foundation
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
 * Class Version20200110184019
 * @package Database\Migrations\Model
 */
class Version20200110184019 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $builder = new Builder($schema);

        if (!$builder->hasTable("SummitAttendeeTicketFormerHash")) {
            $builder->create("SummitAttendeeTicketFormerHash", function (Table $table) {
                $table->integer("ID", true, false);
                $table->primary("ID");
                $table->timestamp('Created');
                $table->timestamp('LastEdited');
                $table->string("Hash", 255)->setNotnull(false);
                $table->unique("Hash", "Hash");
                $table->integer("SummitAttendeeTicketID", false, false)->setNotnull(false)->setDefault('NULL');
                $table->index("SummitAttendeeTicketID", "SummitAttendeeTicketID");
                $table->foreign("SummitAttendeeTicket", "SummitAttendeeTicketID", "ID", ["onDelete" => "CASCADE"]);
            });
        }
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $builder = new Builder($schema);

        $builder->dropIfExists("SummitAttendeeTicketFormerHash");
    }
}
