<?php

namespace Database\Migrations\Model;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema as Schema;
use LaravelDoctrine\Migrations\Schema\Builder;
use LaravelDoctrine\Migrations\Schema\Table;

class Version20220629180748 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $sql = <<<SQL
ALTER TABLE SummitTicketType ADD Audience enum('All', 'WithInvitation', 'WithoutInvitation') NOT NULL DEFAULT 'All';
SQL;
        $this->addSql($sql);
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        $builder = new Builder($schema);
        if($schema->hasTable("SummitTicketType") && $builder->hasColumn("SummitTicketType", "Audience")) {
            $builder->table('SummitTicketType', function (Table $table) {
                $table->dropColumn('Audience');
            });
        }
    }
}
