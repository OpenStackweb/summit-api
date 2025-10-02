<?php

declare(strict_types=1);

namespace Database\Migrations\Model;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use LaravelDoctrine\Migrations\Schema\Builder;
use LaravelDoctrine\Migrations\Schema\Table;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251002160949 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add SummitID to Ticket Table';
    }

    public function up(Schema $schema): void
    {
        $builder = new Builder($schema);
        if($schema->hasTable("SummitAttendeeTicket") && !$builder->hasColumn("SummitAttendeeTicket", "SummitID")) {
            $builder->table("SummitAttendeeTicket", function (Table $table) {
                $table->integer("SummitID", false, false)->setNotnull(false)->setDefault('NULL');
                $table->index("SummitID", "IDX_SummitAttendeeTicket_SummitID");
                $table->index(["SummitID","ID",], "IDX_SummitAttendeeTicket_SummitID_ID");
                $table->index(["OrderID","ID",], "IDX_SummitAttendeeTicket_OrderID_ID");
                $table->foreign("Summit", "SummitID", "ID", ["onDelete" => "CASCADE"], "FK_SummitAttendeeTicket_Summit");
            });
        }

    }

    public function down(Schema $schema): void
    {
        $builder = new Builder($schema);
        if($schema->hasTable("SummitAttendeeTicket") && $builder->hasColumn("SummitAttendeeTicket", "SummitID")) {
            $builder->table("SummitAttendeeTicket", function (Table $table) {
                $table->dropColumn('SummitID');
            });
        }

    }
}
