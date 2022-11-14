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
/**
 * Class Version20221101163314
 * @package Database\Migrations\Model
 */
final class Version20221101163314 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        //Populate all PresentationActionType-SelectionPlan possible relationships
        //taking into account the current PresentationActionType global order
        $sql = <<<SQL
    INSERT INTO PresentationActionType_SelectionPlan(PresentationActionTypeID, SelectionPlanID, CustomOrder)
    SELECT pat.ID, sp.ID, pat.`Order` FROM PresentationActionType pat INNER JOIN SelectionPlan sp ON pat.SummitID = sp.SummitID;
SQL;
        $this->addSql($sql);
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void { }
}
