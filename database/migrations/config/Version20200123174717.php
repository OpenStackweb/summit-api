<?php namespace Database\Migrations\Config;
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
 * Class Version20200123174717
 * @package Database\Migrations\Config
 */
class Version20200123174717 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $builder = new Builder($schema);

        if(!$builder->hasTable("endpoint_api_authz_groups")) {
            $builder->create('endpoint_api_authz_groups', function (Table $table) {
                $table->bigIncrements('id');
                $table->timestamp('created_at')->setDefault('CURRENT_TIMESTAMP');
                $table->timestamp('updated_at')->setDefault('CURRENT_TIMESTAMP');
                $table->bigInteger("api_endpoint_id")->setUnsigned(true);
                $table->index('api_endpoint_id');
                $table->foreign('api_endpoints','api_endpoint_id', 'id');
                $table->string('group_slug', 512);
                $table->unique(['api_endpoint_id', 'group_slug']);
            });
        }
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $builder = new Builder($schema);
        $builder->dropIfExists('endpoint_api_authz_groups');
    }
}
