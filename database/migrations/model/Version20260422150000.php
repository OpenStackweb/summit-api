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
 * Class Version20260422150000
 * @package Database\Migrations\Model
 *
 * Widens the PendingMediaUpload Status column from VARCHAR(20) to VARCHAR(30)
 * to accommodate the new partial status values: PublicStorageUploaded (21 chars)
 * and PrivateStorageUploaded (23 chars).
 */
final class Version20260422150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Widen PendingMediaUpload Status column from VARCHAR(20) to VARCHAR(30) for partial status tracking';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE PendingMediaUpload MODIFY Status VARCHAR(30) NOT NULL DEFAULT 'Pending'");
    }

    public function down(Schema $schema): void
    {
        // Revert back to VARCHAR(20) - will truncate any partial status values
        $this->addSql("ALTER TABLE PendingMediaUpload MODIFY Status VARCHAR(20) NOT NULL DEFAULT 'Pending'");
    }
}
