<?php namespace Database\Migrations\Config;
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
use LaravelDoctrine\Migrations\Schema\Table;
use LaravelDoctrine\Migrations\Schema\Builder;
/**
 * Class Version20190422160409
 * @package Database\Migrations\Config
 */
final class Version20190422160409 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $builder = new Builder($schema);

        if(!$builder->hasTable("apis")) {
            $builder->create('apis', function (Table $table) {
                $table->bigIncrements('id');
                $table->string('name',255);
                $table->unique('name');
                $table->text('description')->setNotnull(false);
                $table->boolean('active')->setDefault(true);
                $table->timestamp('created_at')->setDefault('CURRENT_TIMESTAMP');
                $table->timestamp('updated_at')->setDefault('CURRENT_TIMESTAMP');
            });
        }

        if(!$builder->hasTable("api_scopes")) {
            $builder->create('api_scopes', function (Table $table) {
                $table->bigIncrements('id');
                $table->string('name', 512);
                $table->string('short_description', 512);
                $table->text('description');
                $table->boolean('active')->setNotnull(true);
                $table->boolean('default')->setNotnull(false);
                $table->boolean('system')->setNotnull(false);
                $table->timestamp('created_at')->setDefault('CURRENT_TIMESTAMP');
                $table->timestamp('updated_at')->setDefault('CURRENT_TIMESTAMP');
                // FK
                $table->bigInteger("api_id")->setUnsigned(true)->setNotnull(false);
                $table->index('api_id');
                $table->foreign('apis', 'api_id', 'id');
            });
        }

        if(!$builder->hasTable("api_endpoints")) {
            $builder->create('api_endpoints', function (Table $table) {
                $table->bigIncrements('id');
                $table->boolean('active')->setDefault(true);
                $table->boolean('allow_cors')->setDefault(true);
                $table->boolean('allow_credentials')->setDefault(true);
                $table->text('description')->setNotnull(false);
                $table->string('name', 255);
                $table->unique('name');
                $table->timestamp('created_at')->setDefault('CURRENT_TIMESTAMP');
                $table->timestamp('updated_at')->setDefault('CURRENT_TIMESTAMP');
                $table->text("route");
                $table->getTable()->addColumn('http_method', 'array');
                $table->bigInteger("rate_limit")->setUnsigned(true)->setDefault(0);
                $table->bigInteger("rate_limit_decay")->setUnsigned(true)->setDefault(0);
                //FK
                $table->bigInteger("api_id")->setUnsigned(true);
                $table->index('api_id');
                $table->foreign('apis','api_id', 'id');
            });
        }

        if(!$builder->hasTable("endpoint_api_scopes")) {
            $builder->create('endpoint_api_scopes', function (Table $table) {
                $table->bigIncrements('id');
                $table->timestamp('created_at')->setDefault('CURRENT_TIMESTAMP');
                $table->timestamp('updated_at')->setDefault('CURRENT_TIMESTAMP');
                $table->bigInteger("api_endpoint_id")->setUnsigned(true);
                $table->index('api_endpoint_id');
                $table->foreign('api_endpoints','api_endpoint_id', 'id');
                // FK 2
                $table->bigInteger("scope_id")->setUnsigned(true);
                $table->index('scope_id');
                $table->foreign('api_scopes','scope_id', 'id');

            });
        }

        if(!$builder->hasTable("ip_rate_limit_routes")) {
            $builder->create('ip_rate_limit_routes', function (Table $table) {
                $table->bigIncrements('id');
                $table->string('ip',255);
                $table->text("route");
                $table->boolean('active')->setDefault(true);
                //$table->getTable()->addColumn('http_method',  enum ('GET', 'HEAD', 'POST', 'PUT', 'DELETE', 'TRACE', 'CONNECT', 'OPTIONS', 'PATCH'));
                $table->getTable()->addColumn('http_method', 'array');
                $table->bigInteger("rate_limit")->setUnsigned(true)->setDefault(0);
                $table->bigInteger("rate_limit_decay")->setUnsigned(true)->setDefault(0);
                $table->timestamp('created_at')->setDefault('CURRENT_TIMESTAMP');
                $table->timestamp('updated_at')->setDefault('CURRENT_TIMESTAMP');

            });
        }

    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $builder = new Builder($schema);

        $builder->dropIfExists('endpoint_api_scopes');

        $builder->dropIfExists('apis');

        $builder->dropIfExists('api_scopes');

        $builder->dropIfExists('api_endpoints');

        $builder->dropIfExists('ip_rate_limit_routes');

    }
}
