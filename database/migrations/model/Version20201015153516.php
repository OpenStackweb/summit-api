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

/**
 * Class Version20201015153516
 * @package Database\Migrations\Model
 */
class Version20201015153516 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // migrate data
        $sql = <<<SQL
INSERT INTO SponsorUserInfoGrant (ID, Created, LastEdited, ClassName, AllowedUserID, SponsorID)
SELECT SponsorBadgeScan.ID, 
SponsorBadgeScan.Created, 
SponsorBadgeScan.LastEdited, 
SponsorBadgeScan.ClassName, 
NULL,
SponsorBadgeScan.SponsorID 
FROM SponsorBadgeScan;
SQL;
        $this->addSql($sql);

        // update PK
        $sql = <<<SQL
alter table SponsorBadgeScan modify ID int not null;
SQL;
        $this->addSql($sql);

        // FK inheritance
        $sql = <<<SQL
        ALTER TABLE SponsorBadgeScan ADD CONSTRAINT FK_SponsorBadgeScan_SponsorUserInfoGrant FOREIGN KEY (ID) REFERENCES SponsorUserInfoGrant (ID) ON DELETE CASCADE;
SQL;
        $this->addSql($sql);

        // DROP IDX
        $sql = <<<SQL
drop index SponsorID on SponsorBadgeScan;
SQL;
        $this->addSql($sql);

        $sql = <<<SQL
alter table SponsorBadgeScan drop column SponsorID;
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
