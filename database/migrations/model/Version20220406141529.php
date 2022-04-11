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
 * Class Version20220406141529
 * @package Database\Migrations\Model
 */
final class Version20220406141529 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $sql = <<<SQL
UPDATE SummitSelectedPresentationList A
INNER JOIN ( SELECT SummitSelectedPresentationListID, MAX(SelectionPlanID) AS SelectionPlanID
    FROM ( SELECT DISTINCT SummitSelectedPresentationListID , Presentation.SelectionPlanID
    FROM `SummitSelectedPresentation`
    INNER JOIN Presentation ON Presentation.ID = SummitSelectedPresentation.PresentationID
    WHERE Presentation.SelectionPlanID IS NOT NULL
          AND EXISTS (SELECT 1 FROM SummitSelectedPresentationList B WHERE B.ID = SummitSelectedPresentation.SummitSelectedPresentationListID)
          AND EXISTS (SELECT 1 FROM SelectionPlan WHERE SelectionPlan.ID = Presentation.SelectionPlanID )
          ORDER BY SummitSelectedPresentationListID ) AS Q1
    GROUP BY SummitSelectedPresentationListID ) AS Q2
ON Q2.SummitSelectedPresentationListID = A.ID
    SET A.SelectionPlanID = Q2.SelectionPlanID;
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
