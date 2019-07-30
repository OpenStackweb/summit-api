<?php

namespace Database\Migrations\Model;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema as Schema;
use LaravelDoctrine\Migrations\Schema\Builder;
use LaravelDoctrine\Migrations\Schema\Table;

class Version20200904155247 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $builder = new Builder($schema);
        if($schema->hasTable("PresentationCategory") && !$builder->hasColumn("PresentationCategory","Color") ) {
            $builder->table('PresentationCategory', function (Table $table) {
                $table->string('Color')->setNotnull(false)->setLength(50);
            });
        }
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $builder = new Builder($schema);
        if($schema->hasTable("PresentationCategory") && $builder->hasColumn("PresentationCategory","Color") ) {
            $builder->table('PresentationCategory', function (Table $table) {
                $table->dropColumn('Color');
            });
        }
    }
}
