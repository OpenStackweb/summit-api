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
use LaravelDoctrine\Migrations\Schema\Builder;
use LaravelDoctrine\Migrations\Schema\Table;

/**
 * Class Version20260421150000
 * @package Database\Migrations\Model
 *
 * Creates the PendingMediaUpload table for cron-based Dropbox upload processing.
 * Replaces the queue-based ProcessMediaUpload job to handle Dropbox 429 rate-limits
 * gracefully using Retry-After header-based backoff.
 */
final class Version20260421150000 extends AbstractMigration
{
    private const TableName = 'PendingMediaUpload';

    public function getDescription(): string
    {
        return 'Create PendingMediaUpload table for cron-based media upload processing';
    }

    public function up(Schema $schema): void
    {
        $builder = new Builder($schema);

        if (!$builder->hasTable(self::TableName)) {
            $builder->create(self::TableName, function (Table $table) {
                // Primary key
                $table->integer('ID', true, false);
                $table->primary('ID');

                // SilverStripe base model timestamps
                $table->timestamp('Created')->setNotnull(true);
                $table->timestamp('LastEdited')->setNotnull(true);

                // Foreign keys
                $table->integer('SummitID', false, false)->setNotnull(true);
                $table->index('SummitID', 'IDX_SummitID');
                $table->foreign(
                    'Summit',
                    'SummitID',
                    'ID',
                    [],
                    'FK_PendingMediaUpload_Summit'
                );

                $table->integer('MediaUploadTypeID', false, false)->setNotnull(true);
                $table->index('MediaUploadTypeID', 'IDX_MediaUploadTypeID');
                $table->foreign(
                    'SummitMediaUploadType',
                    'MediaUploadTypeID',
                    'ID',
                    [],
                    'FK_PendingMediaUpload_MediaUploadType'
                );

                // Storage paths
                $table->string('PublicPath', 500)->setNotnull(false);
                $table->string('PrivatePath', 500)->setNotnull(false);
                $table->string('FileName', 255)->setNotnull(true);
                $table->string('TempFilePath', 500)->setNotnull(true);

                // Status tracking
                $table->string('Status', 20)->setNotnull(true)->setDefault('Pending');
                $table->index('Status', 'IDX_Status');

                $table->text('ErrorMessage')->setNotnull(false);
                $table->integer('Attempts', false, false)->setNotnull(true)->setDefault(0);
                $table->timestamp('ProcessedDate')->setNotnull(false);
            });
        }
    }

    public function down(Schema $schema): void
    {
        $builder = new Builder($schema);
        if ($builder->hasTable(self::TableName)) {
            $builder->dropIfExists(self::TableName);
        }
    }
}
