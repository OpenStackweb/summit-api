<?php namespace Database\Migrations\Model;
/**
 * Copyright 2019 OpenStack Foundation
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
 * Class Version20190529142927
 * @package Database\Migrations\Model
 */
class Version20190529142927 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $builder = new Builder($schema);
        if(!$schema->hasTable("SummitRoomReservation")) {
            $builder->create('SummitRoomReservation', function (Table $table) {
                $table->integer("ID", true, false);
                $table->primary("ID");
                $table->string('ClassName')->setDefault("SummitRoomReservation");
                $table->index("ClassName", "ClassName");
                $table->timestamp('Created');
                $table->timestamp('LastEdited');
                $table->timestamp('ApprovedPaymentDate')->setNotnull(false);
                $table->timestamp('StartDateTime')->setNotnull(false);
                $table->timestamp('EndDateTime')->setNotnull(false);
                $table->string("Status");
                $table->string("LastError");
                $table->string("PaymentGatewayCartId", 512);
                $table->decimal("Amount", 9, 2)->setDefault('0.00');
                $table->string("Currency", 3);
                $table->index("PaymentGatewayCartId", "PaymentGatewayCartId");
                $table->integer("OwnerID", false, false)->setNotnull(false);
                $table->index("OwnerID", "OwnerID");
                //$table->foreign("Member", "OwnerID", "ID");
                $table->integer("RoomID", false, false)->setNotnull(false);
                $table->index("RoomID", "RoomID");
                //$table->foreign("SummitBookableVenueRoom", "RoomID", "ID");
            });
        }
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        (new Builder($schema))->drop('SummitRoomReservation');
    }
}
