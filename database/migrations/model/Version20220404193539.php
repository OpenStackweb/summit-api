<?php

namespace Database\Migrations\Model;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema as Schema;
use LaravelDoctrine\Migrations\Schema\Builder;
use LaravelDoctrine\Migrations\Schema\Table;

class Version20220404193539 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $builder = new Builder($schema);
        if($schema->hasTable("SelectionPlan") && !$builder->hasColumn("SelectionPlan", "PresentationCreatorNotificationEmailTemplate")) {
            $builder->table('SelectionPlan', function (Table $table) {
                $table->string('PresentationCreatorNotificationEmailTemplate')->setNotnull(true)->setDefault("");
            });
        }
        if($schema->hasTable("SelectionPlan") && !$builder->hasColumn("SelectionPlan", "PresentationModeratorNotificationEmailTemplate")) {
            $builder->table('SelectionPlan', function (Table $table) {
                $table->string('PresentationModeratorNotificationEmailTemplate')->setNotnull(true)->setDefault("");
            });
        }
        if($schema->hasTable("SelectionPlan") && !$builder->hasColumn("SelectionPlan", "PresentationSpeakerNotificationEmailTemplate")) {
            $builder->table('SelectionPlan', function (Table $table) {
                $table->string('PresentationSpeakerNotificationEmailTemplate')->setNotnull(true)->setDefault("");
            });
        }
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        $builder = new Builder($schema);
        if($schema->hasTable("SelectionPlan") && $builder->hasColumn("SelectionPlan", "PresentationCreatorNotificationEmailTemplate")) {
            $builder->table('SelectionPlan', function (Table $table) {
                $table->dropColumn('PresentationCreatorNotificationEmailTemplate');
            });
        }
        if($schema->hasTable("SelectionPlan") && $builder->hasColumn("SelectionPlan", "PresentationModeratorNotificationEmailTemplate")) {
            $builder->table('SelectionPlan', function (Table $table) {
                $table->dropColumn('PresentationModeratorNotificationEmailTemplate');
            });
        }
        if($schema->hasTable("SelectionPlan") && $builder->hasColumn("SelectionPlan", "PresentationSpeakerNotificationEmailTemplate")) {
            $builder->table('SelectionPlan', function (Table $table) {
                $table->dropColumn('PresentationSpeakerNotificationEmailTemplate');
            });
        }
    }
}
