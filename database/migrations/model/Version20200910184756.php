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

/**
 * Class Version20200910184756
 * @package Database\Migrations\Model
 */
class Version20200910184756 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema):void
    {
        $sql = <<<SQL
ALTER TABLE `PresentationMaterial` CHANGE `ClassName` `ClassName` ENUM('PresentationMaterial','PresentationLink','PresentationSlide','PresentationVideo', 'PresentationMediaUpload') CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT 'PresentationMaterial';
SQL;
        $this->addSql($sql);
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema):void
    {
        $sql = <<<SQL
ALTER TABLE `PresentationMaterial` CHANGE `ClassName` `ClassName` ENUM('PresentationMaterial','PresentationLink','PresentationSlide','PresentationVideo') CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT 'PresentationMaterial';
SQL;
        $this->addSql($sql);

    }
}
