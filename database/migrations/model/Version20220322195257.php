<?php

namespace Database\Migrations\Model;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema as Schema;
use LaravelDoctrine\Migrations\Schema\Builder;
use LaravelDoctrine\Migrations\Schema\Table;

class Version20220322195257 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {

        $builder = new Builder($schema);

        if($schema->hasTable("SummitEventType") && !$builder->hasColumn("SummitEventType", "AllowsLocationAndTimeFrameCollision")) {
            $builder->table('SummitEventType', function (Table $table) {
                $table->boolean('AllowsLocationAndTimeFrameCollision')->setNotnull(true)->setDefault(0);
            });
        }

        if($schema->hasTable("PresentationType") && !$builder->hasColumn("PresentationType", "AllowsSpeakerAndEventCollision")) {
            $builder->table('PresentationType', function (Table $table) {
                $table->boolean('AllowsSpeakerAndEventCollision')->setNotnull(true)->setDefault(0);
            });
        }
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        $builder = new Builder($schema);

        if($schema->hasTable("SummitEventType") && $builder->hasColumn("SummitEventType", "AllowsLocationAndTimeFrameCollision")) {
            $builder->table('SummitEventType', function (Table $table) {
                $table->dropColumn('AllowsLocationAndTimeFrameCollision');
            });
        }

        if($schema->hasTable("PresentationType") && $builder->hasColumn("PresentationType", "AllowsSpeakerAndEventCollision")) {
            $builder->table('PresentationType', function (Table $table) {
                $table->dropColumn('AllowsSpeakerAndEventCollision');
            });
        }
    }
}
