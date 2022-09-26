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
 * Class Version20220926134810
 * @package Database\Migrations\Model
 */
final class Version20220926134810 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {

        $sql = <<<SQL
DELETE FROM SummitEvent_Tags WHERE NOT EXISTS ( SELECT 1 FROM SummitEvent WHERE SummitEvent.ID = SummitEvent_Tags.SummitEventID);
ALTER TABLE `SummitEvent_Tags` ADD CONSTRAINT `FK_SummitEvent_Tags_SummitEvent` FOREIGN KEY (`SummitEventID`) REFERENCES `SummitEvent`(`ID`) ON DELETE CASCADE ON UPDATE RESTRICT;
ALTER TABLE `SummitEvent_Tags` ADD CONSTRAINT `FK_SummitEvent_Tags_Tag` FOREIGN KEY (`TagID`) REFERENCES `Tag`(`ID`) ON DELETE CASCADE ON UPDATE RESTRICT;
ALTER TABLE `TrackTagGroup_AllowedTags` ADD CONSTRAINT `FK_TrackTagGroup_AllowedTags_TrackTagGroupID` FOREIGN KEY (`TrackTagGroupID`) REFERENCES `TrackTagGroup`(`ID`) ON DELETE CASCADE ON UPDATE RESTRICT;
ALTER TABLE `TrackTagGroup_AllowedTags` ADD CONSTRAINT `FK_TrackTagGroup_AllowedTags_Tag` FOREIGN KEY (`TagID`) REFERENCES `Tag`(`ID`) ON DELETE CASCADE ON UPDATE RESTRICT;
ALTER TABLE `DefaultTrackTagGroup_AllowedTags` ADD CONSTRAINT `FK_DefaultTrackTagGroup_AllowedTags_DefaultTrackTagGroup` FOREIGN KEY (`DefaultTrackTagGroupID`) REFERENCES `DefaultTrackTagGroup`(`ID`) ON DELETE CASCADE ON UPDATE RESTRICT;
ALTER TABLE `DefaultTrackTagGroup_AllowedTags` ADD CONSTRAINT `FK_DefaultTrackTagGroup_AllowedTags_Tag` FOREIGN KEY (`TagID`) REFERENCES `Tag`(`ID`) ON DELETE CASCADE ON UPDATE RESTRICT;
ALTER TABLE `PresentationCategory_AllowedTags` ADD CONSTRAINT `FK_PresentationCategory_AllowedTags_PresentationCategory` FOREIGN KEY (`PresentationCategoryID`) REFERENCES `PresentationCategory`(`ID`) ON DELETE CASCADE ON UPDATE RESTRICT;
ALTER TABLE `PresentationCategory_AllowedTags` ADD CONSTRAINT `FK_PresentationCategory_AllowedTags_Tag` FOREIGN KEY (`TagID`) REFERENCES `Tag`(`ID`) ON DELETE CASCADE ON UPDATE RESTRICT;
SQL;
        foreach(explode(';', $sql) as $statement){
            if(!empty($statement))
              $this->addSql($statement);
        }

    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {

    }
}
