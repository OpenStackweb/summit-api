<?php namespace Database\Migrations\Model;
/**
 * Copyright 2021 OpenStack Foundation
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
use LaravelDoctrine\Migrations\Schema\Builder;
use LaravelDoctrine\Migrations\Schema\Table;

/**
 * Class Version20210903180455
 * @package Database\Migrations\Model
 */
class Version20210903180455 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema):void
    {
        $builder = new Builder($schema);

        if(!$schema->hasTable("SummitRefundRequest")) {
            $builder->create('SummitRefundRequest', function (Table $table) {
                $table->integer("ID", true, false);
                $table->primary("ID");
                $table->timestamp('Created');
                $table->timestamp('LastEdited');
                $table->string('ClassName')->setNotnull(true);
                // fields
                $table->decimal("RefundedAmount", 9, 2)->setDefault('0.00');
                $table->text("Notes")->setNotnull(false);
                $table->timestamp('ActionDate')->setNotnull(false);
                $table->string("Status")->setNotnull(false)->setDefault("Requested");
                $table->text("PaymentGatewayResult")->setNotnull(false)->setDefault('NULL');
                // relations
                $table->integer("RequestedByID", false, false)->setNotnull(false)->setDefault('NULL');
                $table->index("RequestedByID", "RequestedByID");
                $table->foreign("Member", "RequestedByID", "ID", ["onDelete" => "CASCADE"]);

                $table->integer("ActionByID", false, false)->setNotnull(false)->setDefault('NULL');
                $table->index("ActionByID", "ActionByID");
                $table->foreign("Member", "ActionByID", "ID", ["onDelete" => "CASCADE"]);
            });
        }

        if(!$schema->hasTable("SummitAttendeeTicketRefundRequest")) {
            $builder->create('SummitAttendeeTicketRefundRequest', function (Table $table) {
                $table->integer("ID", false, false);
                $table->primary("ID");
                $table->foreign("SummitRefundRequest", "ID", "ID", ["onDelete" => "CASCADE"]);

                $table->integer("TicketID", false, false)->setNotnull(false)->setDefault('NULL');
                $table->index("TicketID", "TicketID");
                $table->foreign
                (
                    "SummitAttendeeTicket",
                    "TicketID",
                    "ID",
                    ["onDelete" => "CASCADE"],
                    "FK_SummitAttendeeTicketRefundRequest_SummitRefundRequest"
                );
            });
        }
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema):void
    {
        $schema->dropTable("SummitAttendeeTicketRefundRequest");
        $schema->dropTable("SummitRefundRequest");
    }
}
