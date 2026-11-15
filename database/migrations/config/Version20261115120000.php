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
 * Register the sponsor CSV import endpoint.
 *
 * ApiEndpointsSeeder covers fresh installs only (the deploy flow does not re-run seeders), and
 * OAuth2BearerAccessTokenRequestValidator rejects any route missing from api_endpoints, so a
 * deployed environment needs this row inserted by migration or the endpoint is unreachable.
 *
 * Scopes and authz_groups mirror the add-sponsor entry.
 *
 * Idempotent: registerEndpoints() guards via WHERE NOT EXISTS inside the helper.
 */
final class Version20261115120000 extends AbstractMigration
{
    use APIEndpointsMigrationHelper;

    private const API_NAME = 'summits';

    private const ENDPOINTS = [
        [
            'name' => 'import-sponsors',
            'route' => '/api/v1/summits/{id}/sponsors/csv',
            'http_method' => 'POST',
            'scopes' => [
                SummitScopes::WriteSummitData,
            ],
            'authz_groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
            ],
        ],
    ];

    public function getDescription(): string
    {
        return 'Register the sponsor CSV import endpoint.';
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
