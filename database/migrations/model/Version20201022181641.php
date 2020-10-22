<?php

namespace Database\Migrations\Model;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema as Schema;
use LaravelDoctrine\Migrations\Schema\Builder;
use LaravelDoctrine\Migrations\Schema\Table;

class Version20201022181641 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $builder = new Builder($schema);

        if($schema->hasTable("PresentationCategory") && !$builder->hasColumn("PresentationCategory", "IconID")) {
            $builder->table('PresentationCategory', function (Table $table) {

                $table->integer("IconID", false, false)->setNotnull(false)->setDefault('NULL');
                $table->index("IconID", "IconID");
                $table->foreign("File", "IconID", "ID", ["onDelete" => "CASCADE"]);

            });
        }
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $builder = new Builder($schema);

        if($schema->hasTable("PresentationCategory") && $builder->hasColumn("PresentationCategory", "IconID")) {
            $builder->table('PresentationCategory', function (Table $table) {
                $table->dropColumn("IconID");
            });
        }
    }
}
