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
/**\
 * Class Version20220207183951
 * @package Database\Migrations\Model
 */
class Version20220207183951 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {

        $sql = <<<SQL
ALTER TABLE `SummitScheduleConfig` CHANGE `ClassName` `ClassName` 
ENUM('SummitScheduleConfig') 
CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT 'SummitScheduleConfig';
SQL;
        $this->addSql($sql);

        $sql = <<<SQL
ALTER TABLE `SummitScheduleFilterElementConfig` CHANGE `ClassName` `ClassName` 
ENUM('SummitScheduleFilterElementConfig') 
CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT 'SummitScheduleFilterElementConfig';
SQL;
        $this->addSql($sql);

        $sql = <<<SQL
ALTER TABLE `SummitScheduleFilterElementConfig` CHANGE `Type` `Type` 
ENUM('DATE','TRACK','TRACK_GROUPS','COMPANY','LEVEL','SPEAKERS','VENUES','EVENT_TYPES','TITLE','CUSTOM_ORDER','ABSTRACT') 
CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT 'DATE';
SQL;
        $this->addSql($sql);

        $sql = <<<SQL
ALTER TABLE `SummitScheduleConfig` CHANGE `ColorSource` `ColorSource` 
ENUM('EVENT_TYPES','TRACK','TRACK_GROUP') 
CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT 'EVENT_TYPES';
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
