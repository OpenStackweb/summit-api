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
use App\Security\SummitScopes;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Seed the update-draft-event endpoint.
 *
 * Idempotent via WHERE NOT EXISTS in APIEndpointsMigrationHelper.
 */
final class Version20260609175051 extends AbstractMigration
{
    use APIEndpointsMigrationHelper;

    private const API_NAME = 'summits';
    private const ENDPOINT_NAME = 'update-draft-event';
    private const ENDPOINT_ROUTE = '/api/v1/summits/{id}/events/{event_id}/draft';

    private const AUTH_GROUPS = [
        IGroup::SuperAdmins,
        IGroup::Administrators,
        IGroup::SummitAdministrators,
        IGroup::TrackChairsAdmins,
    ];

    private const SCOPES = [
        SummitScopes::WriteSummitData,
        SummitScopes::WriteEventData
    ];

    public function getDescription(): string
    {
        return 'Seed update-draft-event endpoint with scope and authz groups';
    }

    public function up(Schema $schema): void
    {
        $this->addSql($this->insertEndpoint(
            self::API_NAME,
            self::ENDPOINT_NAME,
            self::ENDPOINT_ROUTE,
            'PUT'
        ));

        foreach (self::SCOPES as $scope) {
            $this->addSql($this->insertEndpointScope(
                self::API_NAME,
            self::ENDPOINT_NAME,
                $scope
            ));
        }

        foreach (self::AUTH_GROUPS as $groupSlug) {
            $this->addSql($this->insertEndpointAuthzGroup(self::API_NAME, self::ENDPOINT_NAME, $groupSlug));
        }
    }

    public function down(Schema $schema): void
    {
        foreach (self::AUTH_GROUPS as $groupSlug) {
            $this->addSql($this->deleteEndpointAuthzGroup(self::API_NAME, self::ENDPOINT_NAME, $groupSlug));
        }

        $this->addSql($this->deleteScopesEndpoints(self::API_NAME, self::SCOPES));
        $this->addSql($this->deleteEndpoint(self::API_NAME, self::ENDPOINT_NAME));
    }
}
