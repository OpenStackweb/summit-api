<?php namespace Database\Migrations\Config;
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

use App\Models\Foundation\Main\IGroup;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Allow summit-room-administrators on the endpoints used by the Room Occupancy screen and the Room Manifest report.
 *
 * Idempotent via WHERE NOT EXISTS in APIEndpointsMigrationHelper.
 */
final class Version20260929120000 extends AbstractMigration
{
    use APIEndpointsMigrationHelper;

    private const API_NAME = 'summits';

    private const ENDPOINTS = [
        'get-events-csv',
        'update-event',
        'update-overflow-streaming',
        'delete-overflow-streaming',
        'get-member-from-summit-csv',
    ];

    public function getDescription(): string
    {
        return 'Add summit-room-administrators authz group to room occupancy and room manifest endpoints';
    }

    public function up(Schema $schema): void
    {
        foreach (self::ENDPOINTS as $endpoint) {
            $this->addSql($this->insertEndpointAuthzGroup(self::API_NAME, $endpoint, IGroup::SummitRoomAdministrators));
        }
    }

    public function down(Schema $schema): void
    {
        foreach (self::ENDPOINTS as $endpoint) {
            $this->addSql($this->deleteEndpointAuthzGroup(self::API_NAME, $endpoint, IGroup::SummitRoomAdministrators));
        }
    }
}
