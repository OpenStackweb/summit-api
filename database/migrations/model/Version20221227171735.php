<?php

namespace Database\Migrations\Model;
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
 * Class Version20221227171735
 * @package Database\Migrations\Model
 */
final class Version20221227171735 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $builder = new Builder($schema);
        if (!$schema->hasTable("SummitRegistrationPromoCode_Tags")) {
            $builder->create("SummitRegistrationPromoCode_Tags", function (Table $table) {

                $table->bigInteger("ID", true, false);
                $table->primary("ID");

                $table->integer("SummitRegistrationPromoCodeID", false, false)->setNotnull(false)->setDefault('NULL');
                $table->index("SummitRegistrationPromoCodeID", "SummitRegistrationPromoCodeID");
                $table->foreign("SummitRegistrationPromoCode", "SummitRegistrationPromoCodeID", "ID", ["onDelete" => "CASCADE"], "FK_SummitRegistrationPromoCode_Tags_PromoCode");

                $table->integer("TagID", false, false)->setNotnull(false)->setDefault('NULL');
                $table->index("TagID", "TagID");
                $table->foreign("Tag", "TagID", "ID", ["onDelete" => "CASCADE"], "FK_SummitRegistrationPromoCode_Tags_Tag");

                $table->unique(['SummitRegistrationPromoCodeID', 'TagID']);

            });
        }
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        if ($schema->hasTable("SummitRegistrationPromoCode_Tags")) {
            $schema->dropTable("SummitRegistrationPromoCode_Tags");
        }
    }
}
