<?php namespace Database\Migrations\Model;
/**
 * Copyright 2024 OpenStack Foundation
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 * http://www.apache.org/licenses/LICENSE-2.0
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 **/
use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema as Schema;
/**
 * Class Version20240723185553
 * @package Database\Migrations\Model
 */
final class Version20240723185553 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        /*
         * Precondition
         * needs SUPER GRANT
         * GRANT SUPER ON *.* TO 'user'@'172.16.1.%';
         * FLUSH PRIVILEGES;
         * this function needs to be added by hand on server
         */
        /*
        $sql = <<<SQL
DROP FUNCTION IF EXISTS SUMMIT_ORDER_FINAL_AMOUNT;
SQL;

        $this->addSql($sql);

        $sql = <<<SQL
DELIMITER $$
CREATE FUNCTION SUMMIT_ORDER_FINAL_AMOUNT(OrderID INT)
RETURNS DECIMAL(32,8) DETERMINISTIC
BEGIN
    DECLARE netAmount DECIMAL(32,8);
    DECLARE taxesAmount DECIMAL(32,8);
    SELECT SUM((RawCost - Discount)) FROM SummitAttendeeTicket where SummitAttendeeTicket.OrderID = OrderID INTO netAmount;
    SELECT COALESCE(SUM(SummitAttendeeTicket_Taxes.Amount), 0) FROM SummitAttendeeTicket_Taxes INNER JOIN
    SummitAttendeeTicket ON SummitAttendeeTicket.ID = SummitAttendeeTicket_Taxes.SummitAttendeeTicketID
    WHERE SummitAttendeeTicket.OrderID = OrderID INTO taxesAmount;
    RETURN ( netAmount + taxesAmount) ;
END$$
DELIMITER ;
SQL;

        $this->addSql($sql);
        */
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        $sql = <<<SQL
DROP FUNCTION IF EXISTS SUMMIT_ORDER_FINAL_AMOUNT;
SQL;

        $this->addSql($sql);
    }
}
