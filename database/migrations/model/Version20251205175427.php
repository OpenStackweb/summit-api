<?php

declare(strict_types=1);

namespace Database\Migrations\Model;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251205175427 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'set default to ExtraQuestionTypeValue ClassName';
    }

    public function up(Schema $schema): void
    {
        $sql = <<<SQL
UPDATE ExtraQuestionTypeValue
SET ClassName = 'ExtraQuestionTypeValue'
WHERE ClassName IS NULL
   OR ClassName <> 'ExtraQuestionTypeValue';
SQL;

        $this->addSql($sql);

        $sql= <<<SQL
ALTER TABLE ExtraQuestionTypeValue CHANGE `ClassName` `ClassName` ENUM('ExtraQuestionTypeValue')
     CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NULL DEFAULT 'ExtraQuestionTypeValue';
SQL;

        $this->addSql($sql);

    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs

    }
}
