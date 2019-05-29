<?php

namespace Database\Migrations\Model;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema as Schema;
use LaravelDoctrine\Migrations\Schema\Table;
use LaravelDoctrine\Migrations\Schema\Builder;

class Version20190530205344 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {

        $builder = new Builder($schema);
        if(!$schema->hasTable("SummitBookableVenueRoomAttributeValue")) {
            $builder->create('SummitBookableVenueRoomAttributeValue', function (Table $table) {
                $table->integer("ID", true, false);
                $table->primary("ID");
                $table->string('ClassName')->setDefault("SummitBookableVenueRoomAttributeValue");
                $table->index("ClassName", "ClassName");
                $table->timestamp('Created');
                $table->timestamp('LastEdited');
                $table->string("Value", 255);
                $table->index("Value", "Value");
                $table->integer("TypeID", false, false)->setNotnull(false);
                $table->index("TypeID", "TypeID");
                $table->unique(["TypeID", "Value"], "TypeID_Value");
            });
        }

        if(!$schema->hasTable("SummitBookableVenueRoom_Attributes")) {
            $builder->create('SummitBookableVenueRoom_Attributes', function (Table $table) {
                $table->integer("ID", true, false);
                $table->primary("ID");
                $table->integer("SummitBookableVenueRoomID", false, false)->setNotnull(false)->setDefault(0);
                $table->index("SummitBookableVenueRoomID", "SummitBookableVenueRoomID");
                $table->integer("SummitBookableVenueRoomAttributeValueID", false, false)->setNotnull(false)->setDefault(0);
                $table->index("SummitBookableVenueRoomAttributeValueID", "SummitBookableVenueRoomAttributeValueID");
                $table->unique(["SummitBookableVenueRoomID", "SummitBookableVenueRoomAttributeValueID"], "RoomID_ValueID");
            });
        }
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        (new Builder($schema))->drop('SummitBookableVenueRoomAttributeValue');
        (new Builder($schema))->drop('SummitBookableVenueRoom_Attributes');
    }
}
