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

/**
 * Class Version20221108134005
 * @package Database\Migrations\Model
 */
final class Version20221108134005 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $sql = <<<SQL
INSERT INTO Summit_SponsorshipType
(SponsorshipTypeID, SummitID, CustomOrder)
SELECT SponsorshipType.ID AS SponsorshipTypeID, Summit.ID AS SummitID, `Order` 
FROM SponsorshipType,Summit ORDER BY `Order` ASC;
SQL;

        $this->addSql($sql);

        $sql = <<<SQL
UPDATE Sponsor S
INNER JOIN Summit_SponsorshipType SS ON
    SS.SponsorshipTypeID = S.SponsorshipTypeID AND
    S.SummitID = SS.SummitID
SET S.SummitSponsorshipTypeID = SS.ID;

SQL;
        $this->addSql($sql);

        $sql = <<<SQL

ALTER TABLE Sponsor DROP FOREIGN KEY FK_SponsorSponsorshipType;
SQL;

        $this->addSql($sql);

        $sql = <<<SQL

ALTER TABLE Sponsor DROP COLUMN SponsorshipTypeID;
SQL;

        $this->addSql($sql);

    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {

    }
}
