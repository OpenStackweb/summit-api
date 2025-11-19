<?php

declare(strict_types=1);

namespace Database\Migrations\Model;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251119201347 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Fix PaymentGatewayProfile.ApplicationType ENUM';
    }

    public function up(Schema $schema): void
    {
        $sql = <<<SQL
 ALTER TABLE `PaymentGatewayProfile` CHANGE `ApplicationType` `ApplicationType` ENUM('Registration','BookableRooms')
     CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NULL DEFAULT 'Registration';
SQL;

        $this->addSql($sql);

    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs

    }
}
