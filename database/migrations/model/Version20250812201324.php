<?php namespace Database\Migrations\Model;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use LaravelDoctrine\Migrations\Schema\Builder;
use LaravelDoctrine\Migrations\Schema\Table;
use models\summit\RSVP;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250812201324 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add RSVP ID to RSVP Invitation';
    }

    public function up(Schema $schema): void
    {
        $builder = new Builder($schema);
        if($schema->hasTable("RSVPInvitation") && !$builder->hasColumn("RSVPInvitation", "RSVPID")) {
            $builder->table("RSVPInvitation", function (Table $table) {
                // FK
                $table->integer("RSVPID", false, false)->setNotnull(false)->setDefault('NULL');
                $table->index("RSVPID", "RSVPID");
                $table->foreign("RSVP", "RSVPID", "ID", ["onDelete" => "CASCADE"], 'FK_RSVPInvitation_RSVP');

            });
        }
    }

    public function down(Schema $schema): void
    {
        $builder = new Builder($schema);
        if($schema->hasTable("RSVPInvitation") && $builder->hasColumn("RSVPInvitation", "RSVPID")) {
            $builder->table("RSVPInvitation", function (Table $table) {
                // FK
                $table->dropColumn("RSVPID");
            });
        }

    }
}
