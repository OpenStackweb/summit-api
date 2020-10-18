<?php

namespace Database\Migrations\Model;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema as Schema;

class Version20201018045210 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $sql = <<<SQL
        alter table SponsorBadgeScan drop foreign key FK_SponsorBadgeScan_SponsorUserInfoGrant;
SQL;
        $this->addSql($sql);

        $sql = <<<SQL
        alter table SponsorBadgeScan modify ID int not null auto_increment;
SQL;
        $this->addSql($sql);

        $sql = <<<SQL
        ALTER TABLE SponsorBadgeScan ADD CONSTRAINT FK_SponsorBadgeScan_SponsorUserInfoGrant FOREIGN KEY (ID) REFERENCES SponsorUserInfoGrant (ID) ON DELETE CASCADE;
SQL;
        $this->addSql($sql);
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {

    }
}
