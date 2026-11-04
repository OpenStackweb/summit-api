<?php

declare(strict_types=1);

namespace Database\Migrations\Model;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Backfills SummitAttendeeTicket.SummitID from the order for tickets that have an order but no summit,
 * so the registration stats queries can filter on the ticket's own (indexed) SummitID.
 */
final class Version20261021120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Back Fill Ticket table with summit id from orders (remaining rows)';
    }

    public function up(Schema $schema): void
    {
        $sql = <<<SQL
UPDATE SummitAttendeeTicket t
JOIN SummitOrder o ON o.ID = t.OrderID
SET t.SummitID = o.SummitID
WHERE t.SummitID IS NULL AND t.OrderID IS NOT NULL;
SQL;

        $this->addSql($sql);
    }

    public function down(Schema $schema): void
    {
    }
}
