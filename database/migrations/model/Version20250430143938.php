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

use Database\Utils\DBHelpers;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema as Schema;
use Illuminate\Support\Facades\DB;

/**
 * Class Version20250430143938
 * @package Database\Migrations\Model
 */
class Version20250430143938 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        if(!DBHelpers::existsIDX(DB::connection("model")->getDatabaseName(), 'SummitAttendeeTicket', 'IDX_SummitAttendeeTicket_Owner_Status_Active')) {
            $sql = <<<SQL
ALTER TABLE `SummitAttendeeTicket` ADD INDEX `IDX_SummitAttendeeTicket_Owner_Status_Active` (`OwnerID`, `Status`, `IsActive`) USING BTREE;
SQL;
            $this->addSql($sql);
        }
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        if(DBHelpers::existsIDX(DB::connection("model")->getDatabaseName(), 'SummitAttendeeTicket', 'IDX_SummitAttendeeTicket_Owner_Status_Active')) {

            $sql = <<<SQL
ALTER TABLE `SummitAttendeeTicket` DROP INDEX `IDX_SummitAttendeeTicket_Owner_Status_Active`
SQL;
            $this->addSql($sql);
        }
    }
}
