<?php namespace Database\Migrations\Model;
/**
 * Copyright 2024 OpenStack Foundation
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
 * Class Version20240201171103
 * @package Database\Migrations\Model
 */
class Version20240201171103 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema):void
    {
        $sql = <<<SQL
ALTER TABLE ExtraQuestionType MODIFY ClassName 
    enum(
    'ExtraQuestionType',
    'SummitSelectionPlanExtraQuestionType',
    'SummitOrderExtraQuestionType',
    'SummitSponsorExtraQuestionType'
    ) DEFAULT 'SummitOrderExtraQuestionType'
SQL;

        $this->addSql($sql);

        $sql = <<<SQL
ALTER TABLE ExtraQuestionAnswer MODIFY ClassName 
    enum(
    'ExtraQuestionAnswer',
    'SummitOrderExtraQuestionAnswer',
    'PresentationExtraQuestionAnswer',
    'SponsorBadgeScanExtraQuestionAnswer'
    ) DEFAULT 'ExtraQuestionAnswer'
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
