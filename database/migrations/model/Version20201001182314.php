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
use LaravelDoctrine\Migrations\Schema\Builder;
use LaravelDoctrine\Migrations\Schema\Table;

/**
 * Class Version20201001182314
 * @package Database\Migrations\Model
 */
class Version20201001182314 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema):void
    {
        $builder = new Builder($schema);
        if (!$builder->hasTable("Summit_FeaturedSpeakers")) {

            $builder->create("Summit_FeaturedSpeakers", function (Table $table) {
                $table->integer("ID", true, false);
                $table->primary("ID");

                $table->integer("SummitID", false, false)->setNotnull(false)->setDefault('NULL');
                $table->index("SummitID", "SummitID");
                //$table->foreign("Summit", "SummitID", "ID", ["onDelete" => "CASCADE"]);

                $table->integer("PresentationSpeakerID", false, false)->setNotnull(false)->setDefault('NULL');
                $table->index("PresentationSpeakerID", "PresentationSpeakerID");
                //$table->foreign("PresentationSpeaker", "PresentationSpeakerID", "ID", ["onDelete" => "CASCADE"]);
                $table->unique(['SummitID', 'PresentationSpeakerID']);
            });
        }

    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema):void
    {
        $builder = new Builder($schema);
        $builder->dropIfExists("Summit_FeaturedSpeakers");
    }
}
