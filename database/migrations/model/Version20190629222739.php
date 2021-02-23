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
 * Class Version20190629222739
 * @package Database\Migrations\Model
 */
final class Version20190629222739 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema):void
    {
        $builder = new Builder($schema);
        if($schema->hasTable("SummitRoomReservation")) {
            $builder->table('SummitRoomReservation', function (Table $table) {
                $table->dropColumn('RefundedAmount');
                $table->integer("RefundedAmount")->setDefault(0)->setNotnull(true);
                $table->dropColumn('Amount');
                $table->integer("Amount")->setDefault(0)->setNotnull(true);
            });
        }
        if($schema->hasTable("SummitBookableVenueRoom")) {
            $builder->table('SummitBookableVenueRoom', function (Table $table) {
                $table->dropColumn('TimeSlotCost');
                $table->integer("TimeSlotCost")->setDefault(0)->setNotnull(true);
            });
        }
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema):void
    {

    }
}
