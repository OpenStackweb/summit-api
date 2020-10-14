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
 * Class Version20201014161727
 * @package Database\Migrations\Model
 */
final class Version20201014161727 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // migrate data
        $sql = <<<SQL
INSERT INTO SummitMetric (ID, Created, LastEdited, ClassName, IngressDate, OutgressDate, MemberID, Type, SummitID)
SELECT SummitEventAttendanceMetric.ID, 
SummitEventAttendanceMetric.Created, 
SummitEventAttendanceMetric.LastEdited, 
SummitEventAttendanceMetric.ClassName, IngressDate, OutgressDate, MemberID, 'EVENT', SummitEvent.SummitID 
FROM SummitEventAttendanceMetric
INNER JOIN SummitEvent ON SummitEvent.ID = SummitEventAttendanceMetric.SummitEventID;
SQL;
        $this->addSql($sql);

        $sql = <<<SQL
alter table SummitEventAttendanceMetric modify ID int not null;
SQL;
        $this->addSql($sql);

        $sql = <<<SQL
        ALTER TABLE SummitEventAttendanceMetric ADD CONSTRAINT FK_SummitEventAttendanceMetric_SummitMetric FOREIGN KEY (ID) REFERENCES SummitMetric (ID) ON DELETE CASCADE;
SQL;
        $this->addSql($sql);

        $sql = <<<SQL
        ALTER TABLE SummitSponsorMetric ADD CONSTRAINT FK_SummitSponsorMetricc_SummitMetric FOREIGN KEY (ID) REFERENCES SummitMetric (ID) ON DELETE CASCADE;
SQL;
        $this->addSql($sql);

        $sql = <<<SQL
alter table SummitEventAttendanceMetric drop column Created;
SQL;
        $this->addSql($sql);

        $sql = <<<SQL
alter table SummitEventAttendanceMetric drop column LastEdited;
SQL;
        $this->addSql($sql);

        $sql = <<<SQL
alter table SummitEventAttendanceMetric drop column IngressDate;
SQL;
        $this->addSql($sql);

        $sql = <<<SQL
alter table SummitEventAttendanceMetric drop column OutgressDate;
SQL;
        $this->addSql($sql);


        $sql = <<<SQL
alter table SummitEventAttendanceMetric drop column MemberID;
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
