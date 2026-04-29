<?php

namespace Database\Migrations\Model;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema as Schema;

class Version20260429160000 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $sql = <<<SQL
ALTER TABLE `SponsorBadgeScan` MODIFY Notes VARCHAR(1024) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL;
SQL;
        $this->addSql($sql);
    }

    public function down(Schema $schema): void
    {

    }
}
