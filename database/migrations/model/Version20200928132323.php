<?php

namespace Database\Migrations\Model;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema as Schema;
use LaravelDoctrine\Migrations\Schema\Builder;
use LaravelDoctrine\Migrations\Schema\Table;

class Version20200928132323 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $builder = new Builder($schema);
        if($schema->hasTable("PresentationMediaUpload") && !$builder->hasColumn("PresentationMediaUpload","LegacyPathFormat") ) {
            $builder->table('PresentationMediaUpload', function (Table $table) {
                $table->boolean('LegacyPathFormat')->setDefault(true)->setNotnull(true);
            });
        }
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {

    }
}
