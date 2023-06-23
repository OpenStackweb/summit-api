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
 * Class Version20230623102958
 * @package Database\Migrations\Model
 */
class Version20230623102958 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema):void
    {
        // intermediate format
        $sql = <<<SQL
ALTER TABLE SummitEventType MODIFY BlackoutTimes VARCHAR(5);
SQL;
        $this->addSql($sql);

        // data conciliation
        $sql = <<<SQL
      UPDATE SummitEventType SET BlackoutTimes = 'All' WHERE BlackoutTimes = '1';
SQL;
        $this->addSql($sql);

        $sql = <<<SQL
      UPDATE SummitEventType SET BlackoutTimes = 'None' WHERE BlackoutTimes = '0';
SQL;
        $this->addSql($sql);

        // make enum
        $sql = <<<SQL
ALTER TABLE SummitEventType MODIFY BlackoutTimes
enum(
'Final', 'Proposed', 'All', 'None'
) default 'None';
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
