<?php

namespace Database\Migrations\Model;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema as Schema;
use LaravelDoctrine\Migrations\Schema\Table;
use LaravelDoctrine\Migrations\Schema\Builder;

class Version20190530205326 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
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
    public function down(Schema $schema)
    {
        (new Builder($schema))->drop('BookableSummitVenueRoomAttributeType');
    }
}
