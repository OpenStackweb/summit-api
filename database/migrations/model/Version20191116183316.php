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
 * Class Version20191116183316
 * @package Database\Migrations\Model
 */
final class Version20191116183316 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $builder = new Builder($schema);

        if ($builder->hasTable("Summit") && !$builder->hasColumn("Summit", "ExternalRegistrationFeedType")) {
            $builder->table("Summit", function (Table $table) {
                $table->string('ExternalRegistrationFeedType')->setNotnull(false);
                $table->string('ExternalRegistrationFeedApiKey')->setNotnull(false);
            });
        }

        if ($builder->hasTable("SummitOrder") && !$builder->hasColumn("SummitOrder", "ExternalId")) {
            $builder->table("SummitOrder", function (Table $table) {
                $table->string('ExternalId')->setNotnull(false);
            });
        }

        if ($builder->hasTable("SummitAttendee") && !$builder->hasColumn("SummitAttendee", "ExternalId")) {
            $builder->table("SummitAttendee", function (Table $table) {
                $table->string('ExternalId')->setNotnull(false);
            });
        }

        if ($builder->hasTable("SummitRegistrationPromoCode") && !$builder->hasColumn("SummitRegistrationPromoCode", "ExternalId")) {
            $builder->table("SummitRegistrationPromoCode", function (Table $table) {
                $table->string('ExternalId')->setNotnull(false);
            });
        }

    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $builder = new Builder($schema);

        if ($builder->hasTable("Summit") && $builder->hasColumn("Summit", "ExternalRegistrationFeedType")) {
            $builder->table("Summit", function (Table $table) {
                $table->dropColumn('ExternalRegistrationFeedType');
                $table->dropColumn('ExternalRegistrationFeedApiKey');
            });
        }

        if ($builder->hasTable("SummitOrder") && $builder->hasColumn("SummitOrder", "ExternalId")) {
            $builder->table("SummitOrder", function (Table $table) {
                $table->dropColumn('ExternalId');
            });
        }

        if ($builder->hasTable("SummitRegistrationPromoCode") && $builder->hasColumn("SummitRegistrationPromoCode", "ExternalId")) {
            $builder->table("SummitRegistrationPromoCode", function (Table $table) {
                $table->dropColumn('ExternalId');
            });
        }

        if ($builder->hasTable("SummitAttendee") && $builder->hasColumn("SummitAttendee", "ExternalId")) {
            $builder->table("SummitAttendee", function (Table $table) {
                $table->dropColumn('ExternalId');
            });
        }
    }
}
