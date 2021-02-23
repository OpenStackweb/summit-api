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
 * Class Version20190506153014
 * @package Database\Migrations\Model
 */
class Version20190506153014 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema):void
    {
        $builder = new Builder($schema);
        if(!$builder->hasTable("PresentationCreatorNotificationEmailRequest")) {
            $this->addSql("CREATE TABLE PresentationCreatorNotificationEmailRequest (PresentationID INT DEFAULT NULL, ID INT NOT NULL, INDEX IDX_B302D49879B1711B (PresentationID), PRIMARY KEY(ID)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;");
            $this->addSql("ALTER TABLE PresentationCreatorNotificationEmailRequest ADD CONSTRAINT FK_B302D49879B1711B FOREIGN KEY (PresentationID) REFERENCES Presentation (ID) ON DELETE CASCADE;");
            $this->addSql("ALTER TABLE PresentationCreatorNotificationEmailRequest ADD CONSTRAINT FK_B302D49811D3633A FOREIGN KEY (ID) REFERENCES EmailCreationRequest (ID) ON DELETE CASCADE");
        }
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema):void
    {
        $builder = new Builder($schema);
        $this->addSql("ALTER TABLE `PresentationCreatorNotificationEmailRequest` DROP FOREIGN KEY `FK_B302D49879B1711B`;");
        $this->addSql("ALTER TABLE `PresentationCreatorNotificationEmailRequest` DROP FOREIGN KEY `FK_B302D49811D3633A`;");
        $builder->dropIfExists('PresentationCreatorNotificationEmailRequest');
    }
}
