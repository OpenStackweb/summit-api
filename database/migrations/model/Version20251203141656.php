<?php

declare(strict_types=1);

namespace Database\Migrations\Model;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251203141656 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'set default to SummitDocument ClassName';
    }

    public function up(Schema $schema): void
    {
        $sql= <<<SQL
ALTER TABLE SummitDocument CHANGE `ClassName` `ClassName` ENUM('SummitDocument')
     CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NULL DEFAULT 'SummitDocument';
SQL;

        $this->addSql($sql);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs

    }
}
