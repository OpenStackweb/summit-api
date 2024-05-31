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
 * Class Version20240531145157
 * @package Database\Migrations\Model
 */
final class Version20240531145157 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        /*
         * Precondition
         * needs SUPER GRANT
         * GRANT SUPER ON *.* TO 'user'@'172.16.1.%';
         * FLUSH PRIVILEGES;
         */
        $sql = <<<SQL
DROP FUNCTION IF EXISTS REVIEW_STATUS;
SQL;

        $this->addSql($sql);

        $sql = <<<SQL
CREATE FUNCTION REVIEW_STATUS(ActivityID INT)
RETURNS VARCHAR(100) DETERMINISTIC
BEGIN
    DECLARE reviewStatus VARCHAR(100);
    SELECT
    CASE
        WHEN S.ClassName <> 'Presentation' THEN 'N/A'
        WHEN SP.ID IS NULL OR P.Status IS NULL THEN 'NotSubmitted'
        WHEN (P.status = 'Received' OR P.status = 'Accepted') AND
            SP.ID IS NOT NULL AND (
            (
                SP.SubmissionLockDownPresentationStatusDate IS NOT NULL AND
                SP.SubmissionLockDownPresentationStatusDate > UTC_TIMESTAMP()
            )
            OR
            (
                (
                    SP.SubmissionBeginDate IS NULL OR SP.SubmissionBeginDate > UTC_TIMESTAMP() OR SP.SubmissionEndDate < UTC_TIMESTAMP() OR SP.SubmissionEndDate IS NULL
                )
                    AND
                (
                    SP.SelectionBeginDate <= UTC_TIMESTAMP() AND SP.SelectionEndDate >= UTC_TIMESTAMP()
                )
            )
        ) THEN 'InReview'
        WHEN (P.status = 'Received' OR P.status = 'Accepted') AND S.Published = 1 THEN 'Published'
        WHEN (P.status = 'Received' OR P.status = 'Accepted') AND
        (
               SP.SelectionBeginDate IS NULL OR SP.SelectionBeginDate > UTC_TIMESTAMP() OR SP.SelectionEndDate < UTC_TIMESTAMP() OR SP.SelectionEndDate IS NULL
        )
        AND EXISTS (
            SELECT 1 FROM SummitSelectedPresentation SSP
            INNER JOIN SummitSelectedPresentationList L ON L.ID = SSP.SummitSelectedPresentationListID
            WHERE
                SSP.PresentationID = P.ID AND
                SSP.Collection = 'selected' AND
                L.ListType = 'Group' AND
                L.ListClass = 'Session'
        )
        THEN 'Accepted'
        WHEN (P.status = 'Received' OR P.status = 'Accepted') AND
        (
            SP.SelectionBeginDate IS NULL OR SP.SelectionBeginDate > UTC_TIMESTAMP() OR SP.SelectionEndDate < UTC_TIMESTAMP() OR SP.SelectionEndDate IS NULL
        )
        AND NOT EXISTS (
            SELECT 1 FROM SummitSelectedPresentation SSP
            INNER JOIN SummitSelectedPresentationList L ON L.ID = SSP.SummitSelectedPresentationListID
            WHERE
                SSP.PresentationID = P.ID AND
                SSP.Collection = 'selected' AND
                L.ListType = 'Group' AND
                L.ListClass = 'Session'
        )
        THEN 'Rejected'
        WHEN (P.Status = 'Received' OR P.Status = 'Accepted') THEN 'Received'
        ELSE 'NotSubmitted'
        END
    FROM SummitEvent S
    LEFT JOIN Presentation P ON P.ID = S.ID
    LEFT JOIN SelectionPlan SP ON P.SelectionPlanID = SP.ID
    WHERE S.ID = ActivityID INTO reviewStatus;
    RETURN reviewStatus;
END
SQL;

        $this->addSql($sql);
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        $sql = <<<SQL
DROP FUNCTION IF EXISTS REVIEW_STATUS;
SQL;

        $this->addSql($sql);
    }
}
