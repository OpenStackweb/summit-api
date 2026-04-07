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

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use LaravelDoctrine\Migrations\Schema\Builder;
use LaravelDoctrine\Migrations\Schema\Table;

/**
 * Class Version20260407003923
 * @package Database\Migrations\Model
 */
final class Version20260407003923 extends AbstractMigration
{
    public function getDescription(): string
    {
        return "Add Source column to SponsorBadgeScan table with default 'QRCode'.";
    }

    public function up(Schema $schema): void
    {
        $builder = new Builder($schema);
        if ($schema->hasTable("SponsorBadgeScan") && !$builder->hasColumn("SponsorBadgeScan", "Source")) {
            $builder->table("SponsorBadgeScan", function (Table $table) {
                $table->string('Source')
                    ->setNotnull(true)
                    ->setLength(255)
                    ->setDefault('QRCode');
            });
        }
    }

    public function down(Schema $schema): void
    {
        $builder = new Builder($schema);
        if ($schema->hasTable("SponsorBadgeScan") && $builder->hasColumn("SponsorBadgeScan", "Source")) {
            $builder->table("SponsorBadgeScan", function (Table $table) {
                $table->dropColumn('Source');
            });
        }
    }
}
