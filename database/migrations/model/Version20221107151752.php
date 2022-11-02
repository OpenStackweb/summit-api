<?php namespace Database\Migrations\Model;
/**
 * Copyright 2022 OpenStack Foundation
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
 * Class Version20221107151752
 * @package Database\Migrations\Model
 */
final class Version20221107151752 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $builder = new Builder($schema);
        if (!$builder->hasTable("Summit_SponsorshipType")) {
            $builder->create("Summit_SponsorshipType", function (Table $table) {

                $table->integer("ID", true, false);
                $table->primary("ID");

                $table->string("WidgetTitle")->setNotnull(false)->setLength(255)->setDefault(null);

                $table->string("LobbyTemplate")->setNotnull(false)->setDefault(null);
                $table->string("ExpoHallTemplate")->setNotnull(false)->setDefault(null);
                $table->string("SponsorPageTemplate")->setNotnull(false)->setDefault(null);
                $table->string("EventPageTemplate")->setNotnull(false)->setDefault(null);
                $table->boolean("SponsorPageShouldUseDisqusWidget")->setNotnull(true)->setDefault(true);
                $table->boolean("SponsorPageShouldUseLiveEventWidget")->setNotnull(true)->setDefault(true);
                $table->boolean("SponsorPageShouldUseScheduleWidget")->setNotnull(true)->setDefault(true);
                $table->boolean("SponsorPageShouldUseBannerWidget")->setNotnull(true)->setDefault(true);
                $table->string("BadgeImageAltText")->setNotnull(false)->setLength(255)->setDefault(null);
                $table->smallInteger("CustomOrder", false, true)->setNotnull(true)->setDefault(1);

                // FK
                $table->integer("BadgeImageID", false, false)->setNotnull(false)->setDefault('NULL');
                $table->index("BadgeImageID", "BadgeImageID");
                $table->foreign("File", "BadgeImageID", "ID", ["onDelete" => "CASCADE"], 'FK_SponsorshipType_Badge_Image');

                $table->integer("SponsorshipTypeID", false, false)->setNotnull(false)->setDefault('NULL');
                $table->index("SponsorshipTypeID", "SponsorshipTypeID");
                $table->foreign("SponsorshipType", "SponsorshipTypeID", "ID", ["onDelete" => "CASCADE"], 'FK_SponsorshipType_Sponsorship');

                $table->integer("SummitID", false, false)->setNotnull(false)->setDefault('NULL');
                $table->index("SummitID", "SummitID");
                $table->foreign("Summit", "SummitID", "ID", ["onDelete" => "CASCADE"], 'FK_SponsorshipType_Summit');

                $table->unique(['SponsorshipTypeID','SummitID']);
            });
        }

    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        $builder = new Builder($schema);

        $builder->dropIfExists("Summit_SponsorshipType");
    }
}
