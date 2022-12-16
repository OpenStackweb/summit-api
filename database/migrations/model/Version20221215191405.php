<?php namespace Database\Migrations\Model;
/**
 * Copyright 2022 OpenStack Foundation
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
use Google\Service\SQLAdmin;

/**
 * Class Version20221215191405
 * @package Database\Migrations\Model
 */
final class Version20221215191405 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        /*
         SELECT DISTINCT
	TABLE_NAME, COLUMN_NAME,NUMERIC_PRECISION,NUMERIC_SCALE
FROM
	INFORMATION_SCHEMA.COLUMNS
WHERE
	DATA_TYPE = ‘decimal’ AND
	TABLE_SCHEMA = 'DB_NAME'
         */

        $scripts = <<<SQL
ALTER TABLE `SummitAttendeeTicket` CHANGE `RawCost` `RawCost` DECIMAL(19,4) NOT NULL DEFAULT '0.00';
ALTER TABLE `SummitAttendeeTicket` CHANGE `Discount` `Discount` DECIMAL(19,4) NOT NULL DEFAULT '0.00';
ALTER TABLE `SummitAttendeeTicket` CHANGE `RefundedAmount` `RefundedAmount` DECIMAL(19,4) NOT NULL DEFAULT '0.00';
ALTER TABLE `SummitOrder` CHANGE `RefundedAmount` `RefundedAmount` DECIMAL(19,4) NOT NULL DEFAULT '0.00';
ALTER TABLE `SummitTicketType` CHANGE `Cost` `Cost` DECIMAL(19,4) NOT NULL DEFAULT '0.00';
ALTER TABLE `SummitAttendeeTicket_Taxes` CHANGE `Amount` `Amount` DECIMAL(32,10) NOT NULL DEFAULT '0.00';
ALTER TABLE `SummitRefundRequest` CHANGE `RefundedAmount` `RefundedAmount` DECIMAL(19,4) NOT NULL DEFAULT '0.00';
ALTER TABLE `SummitRegistrationDiscountCode` CHANGE `DiscountAmount` `DiscountAmount` DECIMAL(19,4) NOT NULL DEFAULT '0.00';
ALTER TABLE `SummitRegistrationDiscountCode_AllowedTicketTypes` CHANGE `DiscountAmount` `DiscountAmount` DECIMAL(19,4) NOT NULL DEFAULT '0.00';
SQL;

        $sentences = explode(";", $scripts);
        foreach ($sentences as $sentence)
        {
            if(empty($sentence)) continue;
            $this->addSql($sentence);
        }
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {

    }
}
