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
 * Class Version20230316192352
 * @package Database\Migrations\Model
 */
final class Version20230316192352 extends AbstractMigration
{
    public function up(Schema $schema):void
    {
        // remove bc now Presentation_Speakers is an entity and not a table a join table
        // and order of the operations on UOW is important ( inserts and then deletes)
        // and if we do Presentation::clearSpeakers() and then re add the same speaker
        // code breaks bc violetes this unique index ( inserts the duplicates first)
        //$this->addSql("ALTER TABLE `Presentation_Speakers` ADD UNIQUE `Presentation_Speaker_Unique_IDX` (`PresentationID`, `PresentationSpeakerID`) USING BTREE;");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema):void
    {
    }
}
