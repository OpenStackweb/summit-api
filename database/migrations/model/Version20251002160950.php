<?php

declare(strict_types=1);

namespace Database\Migrations\Model;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251002160950 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Back Fill Ticket table with summit id from orders';
    }

    public function up(Schema $schema): void
    {
        $sql = <<<SQL
UPDATE SummitAttendeeTicket t
JOIN SummitOrder  o ON o.ID = t.OrderID
SET t.SummitID = o.SummitID
WHERE t.SummitID IS NULL;
SQL;

        $this->addSql($sql);

    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs

    }
}
