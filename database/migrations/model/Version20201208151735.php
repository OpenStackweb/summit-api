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
 * Class Version20201208151735
 * @package Database\Migrations\Model
 */
class Version20201208151735 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema):void
    {
        $sql = <<<SQL
ALTER TABLE SummitEvent MODIFY Level 
enum('Beginner', 'Intermediate', 'Advanced', 'N/A') default 'Beginner';
SQL;
        $this->addSql($sql);

        $sql = <<<SQL
UPDATE SummitEvent SET SummitEvent.Level = (SELECT Presentation.Level
FROM Presentation WHERE Presentation.ID = SummitEvent.ID);
SQL;

        $this->addSql($sql);

        $sql = <<<SQL
alter table Presentation drop column Level;
SQL;
        $this->addSql($sql);

        $sql = <<<SQL
UPDATE SummitEventType set AllowsLevel = 1 where ClassName = 'PresentationType';
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
