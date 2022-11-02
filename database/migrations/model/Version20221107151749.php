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

use Database\Utils\DBHelpers;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema as Schema;
use LaravelDoctrine\Migrations\Schema\Builder;
use LaravelDoctrine\Migrations\Schema\Table;
/**
 * Class Version20221107151749
 * @package Database\Migrations\Model
 */
final class Version20221107151749 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $builder = new Builder($schema);
        if ($builder->hasTable("Sponsor") && !$builder->hasColumn("Sponsor", "SideImageAltText")) {
            $builder->table("Sponsor", function (Table $table) {

                $table->boolean("ShowLogoInEventPage")->setNotnull(true)->setDefault(true);
                $table->string("SideImageAltText")->setNotnull(false)->setLength(255)->setDefault(null);
                $table->string("HeaderImageAltText")->setNotnull(false)->setLength(255)->setDefault(null);
                $table->string("HeaderImageMobileAltText")->setNotnull(false)->setLength(255)->setDefault(null);
                $table->string("CarouselAdvertiseImageAltText")->setNotnull(false)->setLength(255)->setDefault(null);

                $table->integer("FeaturedEventID", false, false)->setNotnull(false)->setDefault('NULL');
                $table->index("FeaturedEventID", "FeaturedEventID");
                $table->foreign("SummitEvent", "FeaturedEventID", "ID", ["onDelete" => "CASCADE"], 'FK_Sponsor_Featured_Event');

                $table->integer("HeaderImageMobileID", false, false)->setNotnull(false)->setDefault('NULL');
                $table->index("HeaderImageMobileID", "HeaderImageMobileID");
                $table->foreign("File", "HeaderImageMobileID", "ID", ["onDelete" => "CASCADE"], 'FK_Sponsor_Header_Image_Mobile');

                $table->integer("CarouselAdvertiseImageID", false, false)->setNotnull(false)->setDefault('NULL');
                $table->index("CarouselAdvertiseImageID", "CarouselAdvertiseImageID");
                $table->foreign("File", "CarouselAdvertiseImageID", "ID", ["onDelete" => "CASCADE"], 'FK_Sponsor_Carousel_Advertise_Image');

            });
        }
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        $builder = new Builder($schema);

        if(DBHelpers::existsFK(env('SS_DATABASE'), "Sponsor", "FK_Sponsor_Header_Image_Mobile")){
            DBHelpers::dropFK(env('SS_DATABASE'), "Sponsor", "FK_Sponsor_Header_Image_Mobile");
        }

        if(DBHelpers::existsFK(env('SS_DATABASE'), "Sponsor", "FK_Sponsor_Carousel_Advertise_Image")){
            DBHelpers::dropFK(env('SS_DATABASE'), "Sponsor", "FK_Sponsor_Carousel_Advertise_Image");
        }

        if(DBHelpers::existsFK(env('SS_DATABASE'), "Sponsor", "FK_Sponsor_Featured_Event")){
            DBHelpers::dropFK(env('SS_DATABASE'), "Sponsor", "FK_Sponsor_Featured_Event");
        }

        if ($builder->hasTable("Sponsor") && $builder->hasColumn("Sponsor", "WidgetTitle")) {
            $builder->table("Sponsor", function (Table $table) {

                $table->dropColumn("ShowLogoInEventPage");
                $table->dropColumn("SideImageAltText");
                $table->dropColumn("HeaderImageAltText");
                $table->dropColumn("HeaderImageMobileAltText");
                $table->dropColumn("CarouselAdvertiseImageAltText");
                $table->dropColumn("FeaturedEventID");
                $table->dropColumn("HeaderImageMobileID");
                $table->dropColumn("CarouselAdvertiseImageID");

            });
        }
    }
}
