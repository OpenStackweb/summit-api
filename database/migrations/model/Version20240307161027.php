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
 * Class Version20240307161027
 * @package Database\Migrations\Model
 */
final class Version20240307161027 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        // SponsorSummitRegistrationPromoCode
        $sql = <<<SQL
UPDATE SponsorSummitRegistrationPromoCode AS PC
INNER JOIN SummitRegistrationPromoCode ON SummitRegistrationPromoCode.ID = PC.ID
INNER JOIN Company ON Company.ID = PC.SponsorID
INNER JOIN Sponsor S ON S.CompanyID = Company.ID
SET PC.SponsorID = S.ID
WHERE
    S.SummitID = SummitRegistrationPromoCode.SummitID;
SQL;
        $this->addSql($sql);

        $sql = <<<SQL
UPDATE SponsorSummitRegistrationPromoCode AS PC
INNER JOIN SummitRegistrationPromoCode ON SummitRegistrationPromoCode.ID = PC.ID
SET PC.SponsorID = NULL
WHERE not exists (select * from Sponsor where Sponsor.ID = PC.SponsorID);

SQL;


        $sql = <<<SQL
ALTER TABLE SponsorSummitRegistrationPromoCode ADD CONSTRAINT FK_SponsorSummitRegistrationPromoCode_Sponsor FOREIGN KEY (SponsorID) REFERENCES Sponsor (ID);     
SQL;


        $this->addSql($sql);

        // SponsorSummitRegistrationDiscountCode

        $sql = <<<SQL
UPDATE SponsorSummitRegistrationDiscountCode AS PC
INNER JOIN SummitRegistrationPromoCode ON SummitRegistrationPromoCode.ID = PC.ID
INNER JOIN Company ON Company.ID = PC.SponsorID
INNER JOIN Sponsor S ON S.CompanyID = Company.ID
SET PC.SponsorID = S.ID
WHERE
    S.SummitID = SummitRegistrationPromoCode.SummitID;
SQL;
        $this->addSql($sql);

        $sql = <<<SQL
UPDATE SponsorSummitRegistrationDiscountCode AS PC
INNER JOIN SummitRegistrationPromoCode ON SummitRegistrationPromoCode.ID = PC.ID
SET PC.SponsorID = NULL
WHERE not exists (select * from Sponsor where Sponsor.ID = PC.SponsorID);
SQL;


        $this->addSql($sql);

        $sql = <<<SQL
ALTER TABLE SponsorSummitRegistrationDiscountCode ADD CONSTRAINT FK_SponsorSummitRegistrationDiscountCode_Sponsor FOREIGN KEY (SponsorID) REFERENCES Sponsor (ID);     
SQL;

        $this->addSql($sql);
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {

        $sql = <<<SQL
ALTER TABLE SponsorSummitRegistrationPromoCode DROP CONSTRAINT FK_SponsorSummitRegistrationPromoCode_Sponsor;     
SQL;

        $this->addSql($sql);

        $sql = <<<SQL
ALTER TABLE SponsorSummitRegistrationDiscountCode DROP CONSTRAINT FK_SponsorSummitRegistrationDiscountCode_Sponsor;     
SQL;

        $this->addSql($sql);
    }
}
