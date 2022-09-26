<?php

namespace Database\Migrations\Model;

use Database\Utils\DBHelpers;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema as Schema;

class Version20220926172743 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
            $sql = <<<SQL
ALTER TABLE `SummitOrder` ADD CONSTRAINT 
    `FK_SummitOrder_Owner` FOREIGN KEY (`OwnerID`) 
        REFERENCES `Member`(`ID`) ON DELETE SET NULL ON UPDATE RESTRICT;
SQL;

            $this->addSql($sql);

        $sql = <<<SQL
ALTER TABLE `SummitOrder` ADD CONSTRAINT 
    `FK_SummitOrder_Summit` FOREIGN KEY (`SummitID`) 
        REFERENCES `Summit`(`ID`) ON DELETE CASCADE ON UPDATE RESTRICT;
SQL;

        $this->addSql($sql);
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {

    }
}
