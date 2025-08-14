<?php namespace Database\Migrations\Model;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use LaravelDoctrine\Migrations\Schema\Builder;
use LaravelDoctrine\Migrations\Schema\Table;
use models\summit\RSVP;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250814135259 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add ActionSource to RSVP table';
    }

    public function up(Schema $schema): void
    {
        $builder = new Builder($schema);
        if($schema->hasTable("RSVP") && !$builder->hasColumn("RSVP", "ActionSource")) {
            $builder->table("RSVP", function (Table $table) {
                $table->string('ActionSource')->setNotnull(false)->setLength(255)->setDefault(null);
            });
        }

    }

    public function down(Schema $schema): void
    {
        $builder = new Builder($schema);
        if($schema->hasTable("RSVP") && $builder->hasColumn("RSVP", "ActionSource")) {
            $builder->table("RSVP", function (Table $table) {
                $table->dropColumn('ActionSource');
            });
        }

    }
}
