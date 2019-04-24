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
use LaravelDoctrine\Migrations\Schema\Table;
use LaravelDoctrine\Migrations\Schema\Builder;
/**
 * Class Version20190506153909
 * @package Database\Migrations\Model
 */
class Version20190506153909 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $builder = new Builder($schema);
        if(!$builder->hasTable("PresentationSpeakerNotificationEmailRequest")){
            $this->addSql("CREATE TABLE PresentationSpeakerNotificationEmailRequest (SpeakerID INT DEFAULT NULL, PresentationID INT DEFAULT NULL, ID INT NOT NULL, INDEX IDX_2BFDC212FEC5CBA6 (SpeakerID), INDEX IDX_2BFDC21279B1711B (PresentationID), PRIMARY KEY(ID)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB; ");
            $this->addSql("ALTER TABLE PresentationSpeakerNotificationEmailRequest ADD CONSTRAINT FK_2BFDC212FEC5CBA6 FOREIGN KEY (SpeakerID) REFERENCES PresentationSpeaker (ID); ");
            $this->addSql("ALTER TABLE PresentationSpeakerNotificationEmailRequest ADD CONSTRAINT FK_2BFDC21279B1711B FOREIGN KEY (PresentationID) REFERENCES Presentation (ID);");
            $this->addSql("ALTER TABLE PresentationSpeakerNotificationEmailRequest ADD CONSTRAINT FK_2BFDC21211D3633A FOREIGN KEY (ID) REFERENCES EmailCreationRequest (ID) ON DELETE CASCADE;");
        }
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $builder = new Builder($schema);
        $this->addSql("ALTER TABLE `PresentationSpeakerNotificationEmailRequest` DROP FOREIGN KEY `FK_2BFDC212FEC5CBA6`;");
        $this->addSql("ALTER TABLE `PresentationSpeakerNotificationEmailRequest` DROP FOREIGN KEY `FK_2BFDC21279B1711B`;");
        $this->addSql("ALTER TABLE `PresentationSpeakerNotificationEmailRequest` DROP FOREIGN KEY `FK_2BFDC21211D3633A`;");
        $builder->dropIfExists('PresentationSpeakerNotificationEmailRequest');
    }
}
