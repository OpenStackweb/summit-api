<?php namespace Database\Migrations\Model;
/**
 * Copyright 2025 OpenStack Foundation
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
 * Class Version20250801164552
 * @package Database\Migrations\Model
 */
class Version20250801164552 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema):void
    {
        $sql = <<<SQL
CREATE INDEX SummitID_CreatedByID_CategoryID_TypeID
ON SummitEvent (SummitID, CreatedByID, CategoryID, TypeID);
SQL;
        $this->addSql($sql);

        $sql = <<<SQL
CREATE INDEX Published ON SummitEvent (Published);
SQL;
        $this->addSql($sql);

        $sql = <<<SQL
CREATE INDEX PresentationID_Order_Collection 
ON SummitSelectedPresentation (PresentationID, `Order`, Collection);
SQL;
        $this->addSql($sql);

        $sql = <<<SQL
CREATE INDEX ID_ListType_ListClass
ON SummitSelectedPresentationList (ID, ListType, ListClass);
SQL;
        $this->addSql($sql);
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema):void
    {
        $sql = <<<SQL
DROP INDEX SummitID_CreatedByID_CategoryID_TypeID ON SummitEvent;
SQL;
        $this->addSql($sql);

        $sql = <<<SQL
DROP INDEX Published ON SummitEvent;
SQL;
        $this->addSql($sql);

        $sql = <<<SQL
DROP INDEX PresentationID_Order_Collection ON SummitSelectedPresentation;
SQL;
        $this->addSql($sql);

        $sql = <<<SQL
DROP INDEX ID_ListType_ListClass ON SummitSelectedPresentationList;
SQL;
        $this->addSql($sql);
    }
}
