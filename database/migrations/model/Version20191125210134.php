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
 * Class Version20191125210134
 * @package Database\Migrations\Model
 */
class Version20191125210134 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $sql = <<<SQL
    ALTER TABLE SummitOrder MODIFY OwnerCompany varchar(255);
SQL;
        $this->addSql($sql);

        $sql = <<<SQL
    DROP INDEX Summit_ExternalId ON SummitTicketType;
SQL;
        $this->addSql($sql);

        $sql = <<<SQL
    CREATE INDEX Summit_ExternalId ON SummitTicketType (SummitID, ExternalId);
SQL;
        $this->addSql($sql);


        $sql = <<<SQL
            DROP INDEX Order_Attendee ON SummitAttendeeTicket;
SQL;
        $this->addSql($sql);

        $sql = <<<SQL
         CREATE INDEX Order_Attendee ON SummitAttendeeTicket (ExternalOrderId, ExternalAttendeeId);
SQL;
        $this->addSql($sql);


     $sql = <<<SQL
          DROP INDEX ExternalId ON SummitTicketType;
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
