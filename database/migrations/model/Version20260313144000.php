<?php namespace Database\Migrations\Model;
/**
 * Copyright 2026 OpenStack Foundation
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
 * Class Version20260313144000
 * @package Database\Migrations\Model
 *
 * Adds DEFAULT CURRENT_TIMESTAMP to Created and LastEdited columns on
 * ManyToMany junction tables so Doctrine's ManyToManyPersister can insert
 * rows without explicitly providing these values.
 */
final class Version20260313144000 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $tables = [
            'SummitEventType_SummitTicketType',
            'SummitEvent_SummitTicketType',
        ];

        foreach ($tables as $table) {
            $this->addSql("ALTER TABLE {$table} MODIFY Created datetime NOT NULL DEFAULT CURRENT_TIMESTAMP");
            $this->addSql("ALTER TABLE {$table} MODIFY LastEdited datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
        }
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        $tables = [
            'SummitEventType_SummitTicketType',
            'SummitEvent_SummitTicketType',
        ];

        foreach ($tables as $table) {
            $this->addSql("ALTER TABLE {$table} MODIFY Created datetime NOT NULL");
            $this->addSql("ALTER TABLE {$table} MODIFY LastEdited datetime NOT NULL");
        }
    }
}
