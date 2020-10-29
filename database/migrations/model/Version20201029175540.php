<?php namespace Database\Migrations\Model;
/**
 * Copyright 2019 OpenStack Foundation
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

class Version20201029175540 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $sql = <<<SQL
ALTER TABLE `SummitSponsorMetric` CHANGE `ClassName` `ClassName` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT 'SummitSponsorMetric';
SQL;
        $this->addSql($sql);

        $sql = <<<SQL
UPDATE Sponsor SET CompanyID = NULL
WHERE NOT EXISTS (SELECT ID FROM Company WHERE Company.ID = Sponsor.CompanyID);
SQL;
        $this->addSql($sql);

        $sql = <<<SQL
ALTER TABLE Sponsor ADD CONSTRAINT FK_SponsorCompany FOREIGN KEY (CompanyID) REFERENCES Company (ID) ON DELETE SET NULL;     
SQL;
        $this->addSql($sql);

        $sql = <<<SQL
UPDATE Sponsor SET SponsorshipTypeID = NULL
WHERE NOT EXISTS (SELECT ID FROM SponsorshipType WHERE SponsorshipType.ID = Sponsor.SponsorshipTypeID);
SQL;
        $this->addSql($sql);

        $sql = <<<SQL
ALTER TABLE Sponsor ADD CONSTRAINT FK_SponsorSponsorshipType FOREIGN KEY (SponsorshipTypeID) REFERENCES SponsorshipType (ID) ON DELETE SET NULL;          
SQL;
        $this->addSql($sql);

    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {

    }
}
