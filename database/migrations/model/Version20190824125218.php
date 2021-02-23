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

class Version20190824125218 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema):void
    {
        $sql = <<<SQL
      UPDATE SummitRoomReservation set Status = 'Paid' where Status = 'Payed';
SQL;
        $this->addSql($sql);

        // make enum
        $sql = <<<SQL
ALTER TABLE SummitRoomReservation MODIFY Status 
enum(
'Reserved',
'Paid',
'Error',
'RequestedRefund',
'Refunded',
'Canceled'
) default 'Reserved' null;
SQL;

        $this->addSql($sql);
        $this->addSql($sql);
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema):void
    {

    }
}
