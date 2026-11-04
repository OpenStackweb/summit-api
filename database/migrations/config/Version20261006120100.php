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
 * Register the attendee check-in/check-out log endpoints (see adr/005-attendee-check-in-audit-log.md).
 *
 * ApiEndpointsSeeder covers fresh installs only -- the k8s deploy flow does not re-run seeders --
 * and OAuth2BearerAccessTokenRequestValidator rejects any route missing from api_endpoints with a
 * 400 before the controller runs. A deployed environment therefore needs these rows inserted by
 * migration or the endpoints are unreachable.
 *
 * Scopes and authz_groups mirror get-badge-prints / get-badge-prints-csv. SummitRoomAdministrators
 * is deliberately NOT included. No new scope is introduced.
 *
 * DEPLOY ORDER: this migration must land with or before the application. auth.user reads these
 * rows, and an endpoint registered without them 403s every authenticated member.
 *
 * Idempotent: registerEndpoints() guards via WHERE NOT EXISTS inside the helper.
 */
final class Version20261006120100 extends AbstractMigration
{
    use APIEndpointsMigrationHelper;

    private const API_NAME = 'summits';

    private const AUTHZ_GROUPS = [
        IGroup::SuperAdmins,
        IGroup::Administrators,
        IGroup::SummitAdministrators,
        IGroup::SummitRegistrationAdmins,
    ];

    private const ENDPOINTS = [
        [
            'name' => 'get-attendee-check-in-logs',
            'route' => '/api/v1/summits/{id}/attendees/{attendee_id}/check-in-logs',
            'http_method' => 'GET',
            'scopes' => [
                SummitScopes::ReadAllSummitData,
            ],
            'authz_groups' => self::AUTHZ_GROUPS,
        ],
        [
            'name' => 'get-attendee-check-in-logs-csv',
            'route' => '/api/v1/summits/{id}/attendees/{attendee_id}/check-in-logs/csv',
            'http_method' => 'GET',
            'scopes' => [
                SummitScopes::ReadAllSummitData,
            ],
            'authz_groups' => self::AUTHZ_GROUPS,
        ],
    ];

    public function getDescription(): string
    {
        return 'Register the attendee check-in/check-out log endpoints.';
    }

    public function up(Schema $schema): void
    {
        $this->registerEndpoints(self::API_NAME, self::ENDPOINTS);
    }

    public function down(Schema $schema): void
    {
        $this->unregisterEndpoints(self::API_NAME, array_column(self::ENDPOINTS, 'name'));
    }
}
