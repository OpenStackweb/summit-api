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
 * Class Version20220728135232
 * @package Database\Migrations\Model
 */
final class Version20220728135232 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $sql = <<<SQL
UPDATE `SummitAttendeeBadgePrint` SET SummitBadgeViewTypeID =
    (SELECT SummitBadgeViewType.ID FROM `SummitBadgeViewType` 
        INNER JOIN SummitBadgeViewType_SummitBadgeType ON SummitBadgeViewType_SummitBadgeType.SummitBadgeViewTypeID = SummitBadgeViewType.ID 
        INNER JOIN SummitBadgeType ON SummitBadgeType.ID = SummitBadgeViewType_SummitBadgeType.SummitBadgeTypeID 
        INNER JOIN SummitAttendeeBadge ON SummitAttendeeBadge.BadgeTypeID = SummitBadgeType.ID 
        WHERE SummitBadgeViewType.IsDefault = 1 
          AND SummitBadgeViewType.SummitID = SummitBadgeType.SummitID 
          AND SummitAttendeeBadge.ID = SummitAttendeeBadgePrint.BadgeID)
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
