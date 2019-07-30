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
use LaravelDoctrine\Migrations\Schema\Table;
use LaravelDoctrine\Migrations\Schema\Builder;
/**
 * Class Version20200128184149
 * @package Database\Migrations\Model
 */
class Version20200128184149 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $builder = new Builder($schema);

        if (!$builder->hasTable("PaymentGatewayProfile")) {
            $builder->create("PaymentGatewayProfile", function (Table $table) {

                $table->integer("ID", true, false);
                $table->primary("ID");
                $table->timestamp('Created');
                $table->timestamp('LastEdited');
                $table->string('ClassName');
                $table->string('ApplicationType');
                $table->string('Provider');
                $table->boolean("IsActive");

                $table->integer("SummitID", false, false)->setNotnull(false)->setDefault('NULL');
                $table->index("SummitID", "SummitID");
                $table->foreign("Summit", "SummitID", "ID", ["onDelete" => "CASCADE"]);

            });
        }

        if (!$builder->hasTable("StripePaymentProfile")) {
            $builder->create("StripePaymentProfile", function (Table $table) {

                $table->integer("ID", true, false);
                $table->primary("ID");
                $table->foreign("PaymentGatewayProfile", "ID", "ID", ["onDelete" => "CASCADE"]);

                $table->boolean("IsTestModeEnabled")->setDefault(false);

                /**
                 * Stripe isn’t exclusively in test or live mode at any given point in time.
                 * Instead, Stripe stores test and live transactions and other data completely separate from each other.
                 */

                $table->text("LiveSecretKey")->setDefault('NULL')->setNotnull(false);
                $table->text("LivePublishableKey")->setDefault('NULL')->setNotnull(false);
                $table->text("LiveWebHookSecretKey")->setDefault('NULL')->setNotnull(false);
                $table->text("LiveWebHookId")->setDefault('NULL')->setNotnull(false);

                $table->text("TestSecretKey")->setDefault('NULL')->setNotnull(false);
                $table->text("TestPublishableKey")->setDefault('NULL')->setNotnull(false);
                $table->text("TestWebHookSecretKey")->setDefault('NULL')->setNotnull(false);
                $table->text("TestWebHookId")->setDefault('NULL')->setNotnull(false);
            });
        }
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $builder = new Builder($schema);

        $builder->dropIfExists("StripePaymentProfile");

        $builder->dropIfExists("PaymentGatewayProfile");
    }
}
