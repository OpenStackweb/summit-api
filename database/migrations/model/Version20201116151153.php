<?php namespace Database\Migrations\Model;
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
/**
 * Class Version20201116151153
 * @package Database\Migrations\Model
 */
class Version20201116151153 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema):void
    {
        // make enum
        $sql = <<<SQL
ALTER TABLE SummitMediaUploadType MODIFY PrivateStorageType 
enum(
'None', 'DropBox','Local','Swift'
) default 'None';
SQL;
        $this->addSql($sql);

        $sql = <<<SQL
ALTER TABLE SummitMediaUploadType MODIFY PublicStorageType 
enum(
'None', 'DropBox', 'S3', 'Swift','Local'
) default 'None';
SQL;
        $this->addSql($sql);
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema):void
    {

    }
}
