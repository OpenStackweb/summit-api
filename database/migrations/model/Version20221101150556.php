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
 * Class Version20221101150556
 * @package Database\Migrations\Model
 */
final class Version20221101150556 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $builder = new Builder($schema);
        if ($builder->hasTable("Sponsor") && !$builder->hasColumn("Sponsor", "SideImageID")) {
            $builder->table("Sponsor", function (Table $table) {

                $table->integer("SideImageID", false, false)->setNotnull(false)->setDefault('NULL');
                $table->index("SideImageID", "SideImageID");
                $table->foreign("File", "SideImageID", "ID", ["onDelete" => "CASCADE"], 'FK_Sponsor_Side_Image');

                $table->integer("HeaderImageID", false, false)->setNotnull(false)->setDefault('NULL');
                $table->index("HeaderImageID", "HeaderImageID");
                $table->foreign("File", "HeaderImageID", "ID", ["onDelete" => "CASCADE"], 'FK_Sponsor_Header_Image');

                $table->string("Marquee")->setNotnull(false)->setLength(150)->setDefault(null);
                $table->string("Intro")->setNotnull(false)->setLength(1000)->setDefault(null);
                $table->string("ExternalLink")->setNotnull(false)->setLength(255)->setDefault(null);
                $table->string("VideoLink")->setNotnull(false)->setLength(255)->setDefault(null);
                $table->string("ChatLink")->setNotnull(false)->setLength(255)->setDefault(null);

            });
        }
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {

    }
}
