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
use LaravelDoctrine\Migrations\Schema\Table;
use LaravelDoctrine\Migrations\Schema\Builder;
/**
 * Class Version20191229173636
 * @package Database\Migrations\Model
 */
class Version20191229173636 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema):void
    {
        $sql = <<<SQL
CREATE UNIQUE INDEX SummitAttendee_Email_SummitID ON SummitAttendee (SummitID, Email);
SQL;

        $this->addSql($sql);

        $sql = <<<SQL
CREATE UNIQUE INDEX SummitAttendee_Member_Summit ON SummitAttendee (MemberID, SummitID);
SQL;

        $this->addSql($sql);
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema):void
    {
        $sql = <<<SQL
DROP INDEX SummitAttendee_Email_SummitID ON SummitAttendee;
SQL;

        $this->addSql($sql);
        $sql = <<<SQL
DROP INDEX SummitAttendee_Member_Summit ON SummitAttendee;
SQL;

        $this->addSql($sql);
    }
}
