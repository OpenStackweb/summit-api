<?php

namespace Database\Migrations\Model;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema as Schema;

class Version20240531145157 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $sql = <<<SQL
DROP FUNCTION IF EXISTS REVIEW_STATUS ;
CREATE FUNCTION REVIEW_STATUS(ActivityID INT)
RETURNS VARCHAR(100) DETERMINISTIC
BEGIN
    DECLARE reviewStatus VARCHAR(100);
    SELECT
    CASE
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
                    SP.SubmissionBeginDate > UTC_TIMESTAMP() OR SP.SubmissionEndDate < UTC_TIMESTAMP()
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
            SP.SelectionBeginDate > UTC_TIMESTAMP() OR SP.SelectionEndDate < UTC_TIMESTAMP()
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
            SP.SelectionBeginDate > UTC_TIMESTAMP() OR SP.SelectionEndDate < UTC_TIMESTAMP()
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

    }
}
