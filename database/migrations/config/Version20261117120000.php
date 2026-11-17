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
 * Let summit administrators reach the team selection list endpoints (summit-admin
 * "Track Chairs > Team Lists").
 *
 * The auth.user middleware (UserAuthEndpoint) 403s any member outside the endpoint's authz
 * groups before the controller runs. These three endpoints only listed admins and track chairs,
 * so a summit admin was rejected even though the service check (Summit::isTrackChair) already
 * accepts a summit admin of that summit.
 *
 * ApiEndpointsSeeder covers fresh installs only; deployed environments need this migration.
 * UserAuthEndpoint caches the groups per route/method and the result per member, so the cache
 * must be flushed after deploy for the change to take effect.
 *
 * Idempotent via WHERE NOT EXISTS inside the helper.
 */
final class Version20261117120000 extends AbstractMigration
{
    use APIEndpointsMigrationHelper;

    private const API_NAME = 'summits';

    private const ENDPOINTS = [
        'get-team-selection-list',
        'add-team-selection-list',
        'reorder-selection-list',
    ];

    public function getDescription(): string
    {
        return 'Add summit administrators to the team selection list endpoints authz groups.';
    }

    public function up(Schema $schema): void
    {
        foreach (self::ENDPOINTS as $endpoint) {
            $this->addSql($this->insertEndpointAuthzGroup(self::API_NAME, $endpoint, IGroup::SummitAdministrators));
        }
    }

    public function down(Schema $schema): void
    {
        foreach (self::ENDPOINTS as $endpoint) {
            $this->addSql($this->deleteEndpointAuthzGroup(self::API_NAME, $endpoint, IGroup::SummitAdministrators));
        }
    }
}
