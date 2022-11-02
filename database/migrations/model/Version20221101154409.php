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
 * Class Version20221101154409
 * @package Database\Migrations\Model
 */
final class Version20221101154409 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {

        $builder = new Builder($schema);
        if (!$schema->hasTable("SponsorSocialNetwork")) {
            $builder->create('SponsorSocialNetwork', function (Table $table) {
                $table->integer("ID", true, false);
                $table->primary("ID");
                $table->string('ClassName')->setDefault("SponsorSocialNetwork");
                $table->index("ClassName", "ClassName");
                $table->timestamp('Created');
                $table->timestamp('LastEdited');

                $table->string("Link")->setNotnull(false)->setLength(255)->setDefault(null);
                $table->string("IconCSSClass")->setNotnull(false)->setLength(255)->setDefault(null);
                $table->boolean("IsEnable")->setNotnull(true)->setDefault(true);

                // FK
                $table->integer("SponsorID", false, false)->setNotnull(false)->setDefault('NULL');
                $table->index("SponsorID", "SponsorID");
                $table->foreign("Sponsor", "SponsorID", "ID", ["onDelete" => "CASCADE"], "FK_SponsorSocialNetwork_Sponsor");
            });
        }
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        $builder = new Builder($schema);
        $builder->dropIfExists("SponsorSocialNetwork");
    }
}
