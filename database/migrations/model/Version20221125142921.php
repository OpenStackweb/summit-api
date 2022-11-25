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
 * Class Version20221125142921
 * @package Database\Migrations\Model
 */
final class Version20221125142921 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $builder = new Builder($schema);
        if ($builder->hasTable("Summit_SponsorshipType") && !$builder->hasColumn("Summit_SponsorshipType", "ShouldDisplayOnExpoHallPage")) {
            $builder->table("Summit_SponsorshipType", function (Table $table) {

                $table->boolean("ShouldDisplayOnExpoHallPage")->setNotnull(true)->setDefault(true);
                $table->boolean("ShouldDisplayOnLobbyPage")->setNotnull(true)->setDefault(true);
            });
        }
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        $builder = new Builder($schema);
        if ($builder->hasTable("Summit_SponsorshipType") && $builder->hasColumn("Summit_SponsorshipType", "ShouldDisplayOnExpoHallPage")) {
            $builder->table("Summit_SponsorshipType", function (Table $table) {

                $table->dropColumn("ShouldDisplayOnExpoHallPage");
                $table->dropColumn("ShouldDisplayOnLobbyPage");
            });
        }
    }
}
