<?php namespace Database\Migrations\Model;
/**
 * Copyright 2024 OpenStack Foundation
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

use Doctrine\DBAL\Schema\SchemaException;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema as Schema;
use LaravelDoctrine\Migrations\Schema\Builder;
use LaravelDoctrine\Migrations\Schema\Table;
/**
 * Class Version20240820125052
 * @package Database\Migrations\Model
 */
final class Version20240820125052 extends AbstractMigration
{
    /**
     * @param Schema $schema
     * @throws SchemaException
     */
    public function up(Schema $schema): void
    {
        $builder = new Builder($schema);
        if($schema->hasTable("SummitRegistrationPromoCode") && !$builder->hasColumn("SummitRegistrationPromoCode", "AllowsToDelegate")) {
            $builder->table("SummitRegistrationPromoCode", function (Table $table) {
                $table->boolean("AllowsToDelegate")->setNotnull(true)->setDefault(false);
            });
        }
    }

    /**
     * @param Schema $schema
     * @throws SchemaException
     */
    public function down(Schema $schema): void
    {
        $builder = new Builder($schema);
        if($schema->hasTable("SummitRegistrationPromoCode") && $builder->hasColumn("SummitRegistrationPromoCode", "AllowsToDelegate")) {
            $builder->table("SummitRegistrationPromoCode", function (Table $table) {
                $table->dropColumn("AllowsToDelegate");
            });
        }
    }
}
