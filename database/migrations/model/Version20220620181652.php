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
 * Class Version20220620181652
 * @package Database\Migrations\Model
 */
final class Version20220620181652 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $builder = new Builder($schema);

        if ($builder->hasTable("PaymentGatewayProfile")) {
            $builder->table("PaymentGatewayProfile", function (Table $table) {
                $table->boolean("IsTestModeEnabled")->setDefault(false);
                $table->text("LiveSecretKey")->setDefault('NULL')->setNotnull(false);
                $table->text("LivePublishableKey")->setDefault('NULL')->setNotnull(false);
                $table->text("TestSecretKey")->setDefault('NULL')->setNotnull(false);
                $table->text("TestPublishableKey")->setDefault('NULL')->setNotnull(false);
           });
        }
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        $builder = new Builder($schema);

        if ($builder->hasTable("PaymentGatewayProfile") && $builder->hasColumn("PaymentGatewayProfile", "IsTestModeEnabled")) {
            $builder->table("PaymentGatewayProfile", function (Table $table) {
                $table->dropColumn("IsTestModeEnabled");
                $table->dropColumn("LiveSecretKey");
                $table->dropColumn("LivePublishableKey");
                $table->dropColumn("TestSecretKey");
                $table->dropColumn("TestPublishableKey");
            });
        }
    }
}
