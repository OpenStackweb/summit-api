<?php namespace Database\Migrations\Model;
/**
 * Copyright 2025 OpenStack Foundation
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
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260220195027 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'update tax rate precision';
    }

    public function up(Schema $schema): void
    {
        $sql = <<<SQL
ALTER TABLE `SummitTaxType` CHANGE `Rate` `Rate` DECIMAL(9,5) NOT NULL DEFAULT '0.00000';
SQL;

        $this->addSql($sql);

        $sql = <<<SQL
ALTER TABLE `SummitAttendeeTicket_Taxes` CHANGE `Rate` `Rate` DECIMAL(9,5) NOT NULL DEFAULT '0.00000';
SQL;

        $this->addSql($sql);

    }

    public function down(Schema $schema): void
    {
        $sql = <<<SQL
ALTER TABLE `SummitTaxType` CHANGE `Rate` `Rate` DECIMAL(9,2) NOT NULL DEFAULT '0.00';
SQL;

        $this->addSql($sql);

        $sql = <<<SQL
ALTER TABLE `SummitAttendeeTicket_Taxes` CHANGE `Rate` `Rate` DECIMAL(9,2) NOT NULL DEFAULT '0.00';
SQL;

        $this->addSql($sql);

    }
}
