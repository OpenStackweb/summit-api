<?php

namespace Database\Migrations\Model;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema as Schema;
use LaravelDoctrine\Migrations\Schema\Builder;
use LaravelDoctrine\Migrations\Schema\Table;

class Version20221124175204 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $builder = new Builder($schema);
        if (!$builder->hasTable("AuditLog")) {
            $builder->create("AuditLog", function (Table $table) {
                $table->integer("ID", true, false);
                $table->primary("ID");
                $table->string('ClassName', 255)->setDefault("AuditLog");
                $table->index("ClassName", "ClassName");
                $table->timestamp('Created');
                $table->timestamp('LastEdited');
                $table->text("Action")->setNotnull(true);
                $table->integer("UserID", false, false)->setNotnull(false)->setDefault('NULL');
                $table->index("UserID", "UserID");
                $table->foreign("Member", "UserID", "ID", ["onDelete" => "CASCADE"]);
            });
        }

        if (!$builder->hasTable("SummitAuditLog")) {
            $builder->create("SummitAuditLog", function (Table $table) {
                $table->integer("ID", true, false);
                $table->primary("ID");
                $table->integer("SummitID");
                $table->index("SummitID", "SummitID");

                $table->foreign("AuditLog", "ID", "ID", ["onDelete" => "CASCADE"], 'FK_SummitAuditLog_AuditLog');
            });
        }

        if (!$builder->hasTable("SummitEventAuditLog")) {
            $builder->create("SummitEventAuditLog", function (Table $table) {
                $table->integer("ID", false, false);
                $table->primary("ID");
                $table->integer("EventID");
                $table->index("EventID", "EventID");

                $table->foreign("AuditLog", "ID", "ID", ["onDelete" => "CASCADE"], 'FK_SummitEventAuditLog_AuditLog');
            });
        }
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        $builder = new Builder($schema);
        $builder->dropIfExists('SummitEventAuditLog');
        $builder->dropIfExists('SummitAuditLog');
        $builder->dropIfExists('AuditLog');
    }
}
