<?php namespace Database\Migrations\Model;
/**
 * Copyright 2021 OpenStack Foundation
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
 * Class Version20210903182620
 * @package Database\Migrations\Model
 */
class Version20210903182620 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema):void
    {
        $sql = <<<SQL
ALTER TABLE `SummitRefundRequest` CHANGE `Status` `Status` 
ENUM('Requested','Approved','Rejected') 
CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT 'Requested';
SQL;
        $this->addSql($sql);

        $sql = <<<SQL
ALTER TABLE `SummitRefundRequest` CHANGE `ClassName` `ClassName` 
ENUM('SummitRefundRequest','SummitAttendeeTicketRefundRequest') 
CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT 'SummitRefundRequest';
SQL;
        $this->addSql($sql);
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema):void
    {

    }
}
