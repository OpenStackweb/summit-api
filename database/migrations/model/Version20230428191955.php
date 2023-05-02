<?php namespace Database\Migrations\Model;
/**
 * Copyright 2023 OpenStack Foundation
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

class Version20230428191955 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $builder = new Builder($schema);
        if (!$builder->hasTable("AssignedPromoCodeSpeaker")) {
            $builder->create("AssignedPromoCodeSpeaker", function (Table $table) {
                $table->integer("ID", true, false);
                $table->primary("ID");

                $table->timestamp("RedeemedAt")->setNotnull(false);
                $table->timestamp("SentAt")->setNotnull(false);

                $table->integer("RegistrationPromoCodeID")->setNotnull(false)->setDefault('NULL');
                $table->foreign("SpeakersSummitRegistrationPromoCode",
                    "RegistrationPromoCodeID",
                    "ID",
                    ["onDelete" => "CASCADE"],
                    "FK_AssignedPromoCodeSpeaker_RegistrationPromoCode");

                $table->integer("RegistrationDiscountCodeID")->setNotnull(false)->setDefault('NULL');
                $table->foreign("SpeakersRegistrationDiscountCode",
                    "RegistrationDiscountCodeID",
                    "ID",
                    ["onDelete" => "CASCADE"],
                    "FK_AssignedPromoCodeSpeaker_RegistrationDiscountCode");

                $table->integer("SpeakerID" );
                $table->index("SpeakerID", "SpeakerID");
                $table->foreign("PresentationSpeaker",
                    "SpeakerID",
                    "ID",
                    ["onDelete" => "CASCADE"],
                    "FK_AssignedPromoCodeSpeaker_PresentationSpeaker");
            });
        }
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        $builder = new Builder($schema);
        $builder->dropIfExists('AssignedPromoCodeSpeaker');
    }
}
