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
 * Class Version20190828142430
 * @package Database\Migrations\Model
 */
class Version20190828142430 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $builder = new Builder($schema);
        if(!$schema->hasTable("queue_jobs")) {
            $builder->create('queue_jobs', function (Table $table) {
                $table->bigInteger("id", true, false);
                $table->primary("id");
                $table->string('queue');
                $table->index("queue","queue");
                $table->text("payload");
                $table->unsignedSmallInteger('attempts');
                $table->unsignedInteger('reserved_at')->setNotnull(false);
                $table->unsignedInteger('available_at');
                $table->unsignedInteger('created_at');
            });
        }
        if(!$schema->hasTable("queue_failed_jobs")) {
            $builder->create('queue_failed_jobs', function (Table $table) {
                $table->bigInteger("id", true, false);
                $table->primary("id");
                $table->text('connection');
                $table->text('queue');
                $table->text('payload');
                $table->text('exception');
                $table->timestamp('failed_at')->setDefault('CURRENT_TIMESTAMP');
            });
        }
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $schema->dropTable("failed_jobs");
        $schema->dropTable("jobs");
    }
}
