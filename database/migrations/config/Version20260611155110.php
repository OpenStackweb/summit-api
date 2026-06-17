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
 * Migration to seed the bulk-update-sponsor-services-statistics endpoint.
 *
 * Adds:
 * - 1 api_endpoints row (bulk-update-sponsor-services-statistics)
 * - 1 endpoint_api_scopes association (WriteSummitData)
 * - 3 endpoint_api_authz_groups rows (SuperAdmins, Administrators, SummitAdministrators)
 *
 * All INSERTs are idempotent via WHERE NOT EXISTS.
 */
final class Version20260611155110 extends AbstractMigration
{
    use APIEndpointsMigrationHelper;

    private const API_NAME = 'summits';
    private const ENDPOINT_NAME = 'bulk-update-sponsor-services-statistics';
    private const ENDPOINT_ROUTE = '/api/v1/summits/{id}/sponsors/all/sponsorservices-statistics/bulk';

    private const AUTHZ_GROUPS = [
        IGroup::SuperAdmins,
        IGroup::Administrators,
        IGroup::SummitAdministrators,
    ];

    public function getDescription(): string
    {
        return 'Seed bulk-update-sponsor-services-statistics endpoint with scope and authz groups.';
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->addSql($this->insertEndpoint(
            self::API_NAME,
            self::ENDPOINT_NAME,
            self::ENDPOINT_ROUTE,
            'PUT'
        ));

        $this->addSql($this->insertEndpointScope(self::API_NAME, self::ENDPOINT_NAME, SummitScopes::WriteSummitData));

        foreach (self::AUTHZ_GROUPS as $groupSlug) {
            $this->addSql($this->insertEndpointAuthzGroup(self::API_NAME, self::ENDPOINT_NAME, $groupSlug));
        }
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        foreach (self::AUTHZ_GROUPS as $groupSlug) {
            $this->addSql($this->deleteEndpointAuthzGroup(self::API_NAME, self::ENDPOINT_NAME, $groupSlug));
        }

        $this->addSql($this->deleteScopesEndpoints(self::API_NAME, [SummitScopes::WriteSummitData]));

        $this->addSql($this->deleteEndpoint(self::API_NAME, self::ENDPOINT_NAME));
    }
}
