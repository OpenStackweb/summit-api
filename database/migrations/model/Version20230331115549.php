<?php namespace Database\Migrations\Model;
/**
 * Copyright 2023 OpenStack Foundation
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
 * Class Version20230331115549
 * @package Database\Migrations\Model
 */
final class Version20230331115549 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $sql= <<<SQL
UPDATE SummitAttendee SET MemberID = NULL WHERE NOT EXISTS (SELECT 1 From Member WHERE Member.ID = SummitAttendee.MemberID) AND SummitAttendee.MemberID IS NOT NULL;
ALTER TABLE `SummitAttendee` ADD CONSTRAINT `FK_SummitAttendee_MemberID` FOREIGN KEY (`MemberID`) REFERENCES `Member`(`ID`) ON DELETE SET NULL ON UPDATE RESTRICT;
ALTER TABLE `SummitAttendee` ADD CONSTRAINT `FK_SummitAttendee_SummitID` FOREIGN KEY (`SummitID`) REFERENCES `Summit`(`ID`) ON DELETE CASCADE ON UPDATE RESTRICT;
UPDATE SummitAttendee SET CompanyID = NULL WHERE NOT EXISTS (SELECT 1 From Company WHERE Company.ID = SummitAttendee.CompanyID) AND SummitAttendee.CompanyID IS NOT NULL;
ALTER TABLE `SummitAttendee` ADD CONSTRAINT `FK_SummitAttendee_CompanyID` FOREIGN KEY (`CompanyID`) REFERENCES `Company`(`ID`) ON DELETE SET NULL ON UPDATE RESTRICT;
SQL;

        foreach(explode(';', $sql) as $query){
            if(!empty(trim($query)))
                $this->addSql($query);
        }
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {

    }
}
