<?php namespace Database\Migrations\Model;
/**
 * Copyright 2022 OpenStack Foundation
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
use models\summit\SummitEventAttendanceMetric;

/**
 * Class Version20220815160210
 * @package Database\Migrations\Model
 */
final class Version20220815160210 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $builder = new Builder($schema);

        if($schema->hasTable("SummitEventAttendanceMetric") && !$builder->hasColumns("SummitEventAttendanceMetric", [
            'SummitVenueRoomID',
            "SummitAttendeeID",
            "SubType",
            ])) {
            $builder->table('SummitEventAttendanceMetric', function (Table $table) {

                $table->string("SubType")->setNotnull(true)->setDefault(SummitEventAttendanceMetric::SubTypeVirtual);

                // FK

                $table->integer("SummitVenueRoomID", false, false)->setNotnull(false)->setDefault('NULL');
                $table->index("SummitVenueRoomID", "SummitVenueRoomID");
                $table->foreign("SummitVenueRoom", "SummitVenueRoomID", "ID", ["onDelete" => "CASCADE"], 'FK_SummitEventAttendanceMetric_SummitVenueRoom');

                $table->integer("SummitAttendeeID", false, false)->setNotnull(false)->setDefault('NULL');
                $table->index("SummitAttendeeID", "SummitAttendeeID");
                $table->foreign("SummitAttendee", "SummitAttendeeID", "ID", ["onDelete" => "CASCADE"], 'FK_SummitEventAttendanceMetric_SummitAttendee');

                $table->integer("CreatedByID", false, false)->setNotnull(false)->setDefault('NULL');
                $table->index("CreatedByID", "CreatedByID");
                $table->foreign("Member", "CreatedByID", "ID", ["onDelete" => "CASCADE"], 'FK_SummitEventAttendanceMetric_CreatedBy');

            });
        }
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        $builder = new Builder($schema);

        if($schema->hasTable("SummitEventAttendanceMetric") && $builder->hasColumns("SummitEventAttendanceMetric", [
                'SummitVenueRoomID',
                "SummitAttendeeID",
                "SubType",
            ])) {
            $builder->table('SummitEventAttendanceMetric', function (Table $table) {

                $table->dropColumn("SubType");
                // FK
                $table->dropColumn("SummitVenueRoomID");
                $table->dropColumn("SummitAttendeeID");
                $table->dropColumn("CreatedByID");
            });
        }
    }
}
