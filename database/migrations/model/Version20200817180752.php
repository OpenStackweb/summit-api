<?php namespace Database\Migrations\Model;
/**
 * Copyright 2020 OpenStack Foundation
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
 * Class Version20200817180752
 * @package Database\Migrations\Model
 */
class Version20200817180752 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {

        $indexes = $this->sm->listTableIndexes("Member");
        if(array_key_exists("ExternalUserId", $indexes))
            $this->sm->dropIndex("ExternalUserId", "Member");

        $sql = <<<SQL
ALTER TABLE `Member` CHANGE `ExternalUserId` `ExternalUserId` INT(11) NULL DEFAULT NULL;
SQL;
        $this->addSql($sql);

        $sql = <<<SQL
UPDATE Member set ExternalUserId = NULL WHERE ExternalUserId = 0;
SQL;

        $this->addSql($sql);

        $sql = <<<SQL
ALTER TABLE `Member` ADD UNIQUE `ExternalUserId` (`ExternalUserId`) USING BTREE;
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
