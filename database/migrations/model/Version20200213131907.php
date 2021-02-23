<?php namespace Database\Migrations\Model;
/**
 * Copyright 2020 OpenStack Foundation
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
 * Class Version20200213131907
 * @package Database\Migrations\Model
 */
class Version20200213131907 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema):void
    {
        $builder = new Builder($schema);
        if($schema->hasTable("Summit") && !$builder->hasColumn("Summit", "ScheduleEventDetailUrl")) {
            $builder->table('Summit', function (Table $table) {
                $table->text("ScheduleDefaultPageUrl")->setNotnull(false);
                $table->text("ScheduleDefaultEventDetailUrl")->setNotnull(false);
                // og tags
                $table->text("ScheduleOGSiteName")->setNotnull(false);
                $table->text("ScheduleOGImageUrl")->setNotnull(false);
                $table->text("ScheduleOGImageSecureUrl")->setNotnull(false);
                $table->integer("ScheduleOGImageWidth")->setNotnull(true)->setDefault(0);
                $table->integer("ScheduleOGImageHeight")->setNotnull(true)->setDefault(0);
                // face book app
                $table->text("ScheduleFacebookAppId")->setNotnull(false);
                // ios app
                $table->text("ScheduleIOSAppName")->setNotnull(false);
                $table->text("ScheduleIOSAppStoreId")->setNotnull(false);
                $table->text("ScheduleIOSAppCustomSchema")->setNotnull(false);
                // android app
                $table->text("ScheduleAndroidAppName")->setNotnull(false);
                $table->text("ScheduleAndroidAppPackage")->setNotnull(false);
                $table->text("ScheduleAndroidAppCustomSchema")->setNotnull(false);
                // twitter app
                $table->text("ScheduleTwitterAppName")->setNotnull(false);
                $table->text("ScheduleTwitterText")->setNotnull(false);
            });
        }
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema):void
    {
        $builder = new Builder($schema);
        if($schema->hasTable("Summit") && $builder->hasColumn("Summit", "ScheduleEventDetailUrl")) {
            $builder->table('Summit', function (Table $table) {
                $table->dropColumn("ScheduleDefaultPageUrl");
                $table->dropColumn("ScheduleDefaultEventDetailUrl");
                // og tags
                $table->dropColumn("ScheduleOGSiteName");
                $table->dropColumn("ScheduleOGImageUrl");
                $table->dropColumn("ScheduleOGImageSecureUrl");
                $table->dropColumn("ScheduleOGImageWidth");
                $table->dropColumn("ScheduleOGImageHeight");
                // face book app
                $table->dropColumn("ScheduleFacebookAppId");
                // ios app
                $table->dropColumn("ScheduleIOSAppName");
                $table->dropColumn("ScheduleIOSAppStoreId");
                $table->dropColumn("ScheduleIOSAppCustomSchema");
                // android app
                $table->dropColumn("ScheduleAndroidAppName");
                $table->dropColumn("ScheduleAndroidAppPackage");
                $table->dropColumn("ScheduleAndroidAppCustomSchema");
                // twitter app
                $table->dropColumn("ScheduleTwitterAppName");
                $table->dropColumn("ScheduleTwitterText");
            });
        }
    }
}
