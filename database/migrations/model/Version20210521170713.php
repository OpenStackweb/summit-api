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
 * Class Version20210521170713
 * @package Database\Migrations\Model
 */
class Version20210521170713 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema):void
    {


        $sql = <<<SQL
insert into ExtraQuestionType(ID,ClassName,Created,LastEdited,Name,Type,Label,`Order`, Mandatory, Placeholder)
select
       ID,
       'SummitOrderExtraQuestionType',
        Created,
       LastEdited,Name,Type,Label,`Order`, Mandatory, Placeholder
from SummitOrderExtraQuestionType;
SQL;
        $this->addSql($sql);

        $sql = <<<SQL
alter table SummitOrderExtraQuestionType
drop column ClassName;
SQL;
        $this->addSql($sql);

        $sql = <<<SQL
alter table SummitOrderExtraQuestionType
drop column Created;
SQL;
        $this->addSql($sql);

        $sql = <<<SQL
alter table SummitOrderExtraQuestionType
drop column LastEdited;
SQL;
        $this->addSql($sql);

        $sql = <<<SQL
alter table SummitOrderExtraQuestionType
drop column Name;
SQL;
        $this->addSql($sql);

        $sql = <<<SQL
alter table SummitOrderExtraQuestionType
drop column Type;
SQL;
        $this->addSql($sql);

        $sql = <<<SQL
alter table SummitOrderExtraQuestionType
drop column Label;
SQL;
        $this->addSql($sql);

        $sql = <<<SQL
alter table SummitOrderExtraQuestionType
drop column `Order`;
SQL;
        $this->addSql($sql);

        $sql = <<<SQL
alter table SummitOrderExtraQuestionType
drop column Mandatory;
SQL;
        $this->addSql($sql);

        $sql = <<<SQL
alter table SummitOrderExtraQuestionType
drop column Placeholder;
SQL;
        $this->addSql($sql);

        $sql = <<<SQL
INSERT INTO ExtraQuestionTypeValue (ID,ClassName,LastEdited,Created,Value,Label,`Order`,QuestionID)
SELECT ID,'ExtraQuestionTypeValue',LastEdited,Created,Value,Label,`Order`,QuestionID
FROM SummitOrderExtraQuestionValue;
SQL;
        $this->addSql($sql);

        $sql = <<<SQL
DROP TABLE SummitOrderExtraQuestionValue;
SQL;
        $this->addSql($sql);

        $sql = <<<SQL
INSERT INTO ExtraQuestionAnswer
(ID, Created,LastEdited,ClassName,Value,QuestionID)
SELECT ID,Created,LastEdited,'SummitOrderExtraQuestionAnswer',Value,QuestionID
FROM SummitOrderExtraQuestionAnswer;
SQL;
        $this->addSql($sql);

        $sql = <<<SQL
ALTER TABLE SummitOrderExtraQuestionAnswer
DROP COLUMN ClassName;
SQL;
        $this->addSql($sql);

        $sql = <<<SQL
ALTER TABLE SummitOrderExtraQuestionAnswer
DROP COLUMN LastEdited;
SQL;
        $this->addSql($sql);

        $sql = <<<SQL
ALTER TABLE SummitOrderExtraQuestionAnswer
DROP COLUMN Created;
SQL;
        $this->addSql($sql);

        $sql = <<<SQL
ALTER TABLE SummitOrderExtraQuestionAnswer
DROP COLUMN Value;
SQL;
        $this->addSql($sql);

        $sql = <<<SQL
ALTER TABLE SummitOrderExtraQuestionAnswer
DROP COLUMN QuestionID;
SQL;
        $this->addSql($sql);

        $sql = <<<SQL
ALTER TABLE SummitSelectionPlanExtraQuestionType 
ADD CONSTRAINT JT_SummitSelectionPlanExtraQuestionType_ExtraQuestionType 
FOREIGN KEY (ID) REFERENCES ExtraQuestionType (ID) ON DELETE CASCADE;   
SQL;
        $this->addSql($sql);

        $sql = <<<SQL
ALTER TABLE SummitOrderExtraQuestionType 
ADD CONSTRAINT JT_SummitOrderExtraQuestionType_ExtraQuestionType 
FOREIGN KEY (ID) REFERENCES ExtraQuestionType (ID) ON DELETE CASCADE;     
SQL;
        $this->addSql($sql);

        $sql = <<<SQL
ALTER TABLE SummitOrderExtraQuestionAnswer 
ADD CONSTRAINT JT_SummitOrderExtraQuestionAnswer_ExtraQuestionAnswer 
FOREIGN KEY (ID) REFERENCES ExtraQuestionAnswer (ID) ON DELETE CASCADE;     
SQL;
        $this->addSql($sql);

        $sql = <<<SQL
ALTER TABLE PresentationExtraQuestionAnswer 
ADD CONSTRAINT JT_PresentationExtraQuestionAnswer_ExtraQuestionAnswer 
FOREIGN KEY (ID) REFERENCES ExtraQuestionAnswer (ID) ON DELETE CASCADE;     
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
