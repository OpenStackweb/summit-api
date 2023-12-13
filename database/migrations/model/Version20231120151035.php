<?php namespace Database\Migrations\Model;
/**
 * Copyright 2023 OpenStack Foundation
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
 * Class Version20231120151035
 * @package Database\Migrations\Model
 */
final class Version20231120151035 extends AbstractMigration
{
    /**
     * @param Schema $schema
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function up(Schema $schema): void
    {
        if($schema->hasTable("SummitRegistrationInvitation")) {
            $sql = <<<SQL
ALTER TABLE SummitRegistrationInvitation MODIFY Status
ENUM('Pending', 'Accepted', 'Rejected') default 'Pending';
SQL;
            $this->addSql($sql);

            $sql = <<<SQL
ALTER TABLE SummitRegistrationInvitation RENAME COLUMN AcceptedDate TO ActionDate;
SQL;
            $this->addSql($sql);

            $sql = <<<SQL
UPDATE SummitRegistrationInvitation 
SET Status = CASE WHEN ActionDate IS NULL THEN 'Pending' ELSE 'Accepted' END;
SQL;
            $this->addSql($sql);
        }
    }

    /**
     * @param Schema $schema
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function down(Schema $schema): void
    {
        if($schema->hasTable("SummitRegistrationInvitation")) {
            $sql = <<<SQL
ALTER TABLE SummitRegistrationInvitation RENAME COLUMN ActionDate TO AcceptedDate;
SQL;
            $this->addSql($sql);
        }
    }
}
