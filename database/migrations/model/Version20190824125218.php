<?php

namespace Database\Migrations\Model;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema as Schema;

class Version20190824125218 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $sql = <<<SQL
      UPDATE SummitRoomReservation set Status = 'Paid' where Status = 'Payed';
SQL;
        $this->addSql($sql);

        // make enum
        $sql = <<<SQL
ALTER TABLE SummitRoomReservation MODIFY Status 
enum(
'Reserved',
'Paid',
'Error',
'RequestedRefund',
'Refunded',
'Canceled'
) default 'Reserved' null;
SQL;

        $this->addSql($sql);
        $this->addSql($sql);
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {

    }
}
