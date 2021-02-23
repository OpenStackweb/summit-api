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
 * Class Version20200512174027
 * @package Database\Migrations\Model
 */
class Version20200512174027 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema):void
    {
        $sentence = <<<SQL
alter table SummitEmailFlowType modify ClassName enum('SummitEmailFlowType') charset utf8 default 'SummitEmailFlowType' null;
SQL;

        $this->addSql($sentence);

        $sentence = <<<SQL
alter table SummitEmailEventFlowType modify ClassName enum('SummitEmailEventFlowType') charset utf8 default 'SummitEmailEventFlowType' null;
SQL;

        $this->addSql($sentence);

        $sentence = <<<SQL
alter table SummitEmailEventFlow modify ClassName enum('SummitEmailEventFlow') charset utf8 default 'SummitEmailEventFlow' null;
SQL;

        $this->addSql($sentence);
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema):void
    {

    }
}
