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
use LaravelDoctrine\Migrations\Schema\Builder;
use LaravelDoctrine\Migrations\Schema\Table;

class Version20200713164344 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $builder = new Builder($schema);

        // make enum
        $sql = <<<SQL
ALTER TABLE PresentationMaterial MODIFY ClassName 
enum(
'PresentationSlide', 'PresentationVideo', 'PresentationLink', 'PresentationMediaUpload'
) default 'PresentationSlide';
SQL;
        $this->addSql($sql);

        // make enum
        $sql = <<<SQL
ALTER TABLE SummitMediaFileType MODIFY ClassName 
enum(
'SummitMediaFileType'
) default 'SummitMediaFileType';
SQL;
        $this->addSql($sql);

        // make enum
        $sql = <<<SQL
ALTER TABLE SummitMediaUploadType MODIFY ClassName 
enum(
'SummitMediaUploadType'
) default 'SummitMediaUploadType';
SQL;
        $this->addSql($sql);

        // make enum
        $sql = <<<SQL
ALTER TABLE SummitMediaUploadType MODIFY PrivateStorageType 
enum(
'None', 'DropBox', 'Swift','Local'
) default 'None';
SQL;
        $this->addSql($sql);

        $sql = <<<SQL
ALTER TABLE SummitMediaUploadType MODIFY PublicStorageType 
enum(
'None', 'DropBox', 'Swift','Local'
) default 'None';
SQL;
        $this->addSql($sql);
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $builder = new Builder($schema);
    }
}
