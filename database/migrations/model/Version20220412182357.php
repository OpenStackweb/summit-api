<?php

namespace Database\Migrations\Model;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema as Schema;

class Version20220412182357 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $sql = <<<SQL
INSERT INTO SelectionPlan_SummitEventTypes (SelectionPlanID, SummitEventTypeID)
SELECT SelectionPlan.ID, SummitEventType.ID
FROM SelectionPlan, SummitEventType
INNER JOIN PresentationType ON PresentationType.ID = SummitEventType.ID
WHERE
      SummitEventType.ClassName = 'PresentationType'
      AND PresentationType.ShouldBeAvailableOnCFP = 1
      AND SummitEventType.SummitID = SelectionPlan.SummitID
      AND NOT EXISTS (
            SELECT 1 FROM SelectionPlan_SummitEventTypes
            WHERE
                  SelectionPlan_SummitEventTypes.SelectionPlanID = SelectionPlan.ID
                  AND SelectionPlan_SummitEventTypes.SummitEventTypeID = SummitEventType.ID
    );
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
