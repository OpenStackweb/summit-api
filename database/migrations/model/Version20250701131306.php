<?php namespace Database\Migrations\Model;
/**
 * Copyright 2025 OpenStack Foundation
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
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
/**
 * Class Version20250701131306
 * @package Database\Migrations\Model
 */
final class Version20250701131306 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adds "Individual" to MembershipType enum in Member table';
    }

    public function up(Schema $schema): void
    {
        $sql = <<<SQL
ALTER TABLE `Member` CHANGE `MembershipType` `MembershipType` ENUM('Foundation','Community','None','Individual') CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NULL DEFAULT 'None';
SQL;

        $this->addSql($sql);
    }

    public function down(Schema $schema): void
    {
          $sql = <<<SQL
ALTER TABLE `Member` CHANGE `MembershipType` `MembershipType` ENUM('Foundation','Community','None') CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NULL DEFAULT 'None';
SQL;
        $this->addSql($sql);
    }
}
