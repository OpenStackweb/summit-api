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
 * Class Version20190530205326
 * @package Database\Migrations\Model
 */
class Version20190530205326 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema):void
    {
        $builder = new Builder($schema);
        if(!$schema->hasTable("SummitBookableVenueRoomAttributeType")) {
            $builder->create('SummitBookableVenueRoomAttributeType', function (Table $table) {
                $table->integer("ID", true, false);
                $table->primary("ID");
                $table->string('ClassName')->setDefault("SummitBookableVenueRoomAttributeType");
                $table->index("ClassName", "ClassName");
                $table->timestamp('Created');
                $table->timestamp('LastEdited');
                $table->string("Type", 255);
                $table->index("Type", "Type");
                $table->integer("SummitID", false, false)->setNotnull(false);
                $table->index("SummitID", "SummitID");
                $table->unique(["SummitID", "Type"], "SummitID_Type");
            });
        }
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema):void
    {
        (new Builder($schema))->drop('BookableSummitVenueRoomAttributeType');
    }
}
