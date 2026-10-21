<?php

namespace Database\Migrations\Model;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema as Schema;

/**
 * Member.Company was latin1: a company name with a character outside latin1 (full-width
 * parentheses, Latin Extended) made the IDP member sync fail with 1366 in strict mode.
 * latin1 is a subset of utf8mb4, so existing values convert unchanged.
 * MySQL can not do it in place (ALGORITHM=COPY): writes to Member are blocked while it runs.
 */
class Version20261021170000 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $sql = <<<SQL
ALTER TABLE `Member` MODIFY Company VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL;
SQL;
        $this->addSql($sql);
    }

    public function down(Schema $schema): void
    {

    }
}
