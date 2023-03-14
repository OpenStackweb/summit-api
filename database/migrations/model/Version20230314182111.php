<?php namespace Database\Migrations\Model;
/**
 * Copyright 2023 OpenStack Foundation
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
 * Class Version20230314182111
 * @package Database\Migrations\Model
 */
final class Version20230314182111 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $sql = <<<SQL
DELETE FROM `Presentation_Speakers` WHERE NOT EXISTS ( SELECT 1 FROM PresentationSpeaker WHERE PresentationSpeaker.ID = Presentation_Speakers.PresentationSpeakerID)
SQL;

        $this->addSql($sql);

        $sql = <<<SQL
ALTER TABLE `Presentation_Speakers` ADD CONSTRAINT `FK_Presentation_Speaker_Speaker` FOREIGN KEY (`PresentationSpeakerID`) REFERENCES `PresentationSpeaker`(`ID`) ON DELETE CASCADE ON UPDATE RESTRICT;
SQL;

        $this->addSql($sql);

        $sql = <<<SQL
DELETE FROM `Presentation_Speakers` WHERE NOT EXISTS ( SELECT 1 FROM Presentation WHERE Presentation.ID = Presentation_Speakers.PresentationID)
SQL;

        $this->addSql($sql);

        $sql = <<<SQL
ALTER TABLE `Presentation_Speakers` ADD CONSTRAINT `FK_Presentation_Speaker_Presentation` FOREIGN KEY (`PresentationID`) REFERENCES `Presentation`(`ID`) ON DELETE CASCADE ON UPDATE RESTRICT;
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
