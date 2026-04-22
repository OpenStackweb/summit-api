<?php namespace Database\Migrations\Model;
/**
 * Copyright 2026 OpenStack Foundation
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
 * Class Version20260421200000
 * @package Database\Migrations\Model
 *
 * Adds PresentationMediaUploadID column to PendingMediaUpload table
 * to link pending rows to their source PresentationMediaUpload entity.
 * Used for cleanup when a media upload is deleted before cron processes it.
 */
final class Version20260421200000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add PresentationMediaUploadID column to PendingMediaUpload table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE PendingMediaUpload ADD COLUMN PresentationMediaUploadID INT NOT NULL AFTER MediaUploadTypeID');
        $this->addSql('ALTER TABLE PendingMediaUpload ADD INDEX IDX_PresentationMediaUploadID (PresentationMediaUploadID)');
        $this->addSql('ALTER TABLE PendingMediaUpload ADD CONSTRAINT FK_PendingMediaUpload_PresentationMediaUpload FOREIGN KEY (PresentationMediaUploadID) REFERENCES PresentationMediaUpload (ID) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE PendingMediaUpload DROP FOREIGN KEY FK_PendingMediaUpload_PresentationMediaUpload');
        $this->addSql('ALTER TABLE PendingMediaUpload DROP INDEX IDX_PresentationMediaUploadID');
        $this->addSql('ALTER TABLE PendingMediaUpload DROP COLUMN PresentationMediaUploadID');
    }
}
