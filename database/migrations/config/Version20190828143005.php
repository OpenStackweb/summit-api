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
 * Class Version20190828143005
 * @package Database\Migrations\Config
 */
class Version20190828143005 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->addSql("ALTER TABLE queue_jobs MODIFY payload longtext NOT NULL;");
        $this->addSql("ALTER TABLE queue_failed_jobs MODIFY payload longtext NOT NULL;");
        $this->addSql("ALTER TABLE queue_failed_jobs MODIFY `exception` longtext NOT NULL;");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {

    }
}
