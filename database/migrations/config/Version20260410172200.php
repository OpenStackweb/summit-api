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

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;
use App\Security\MemberScopes;
use App\Models\Foundation\Main\IGroup;

/**
 * Migration to seed the get-member-by-external-id endpoint.
 *
 * Adds:
 * - 1 api_endpoints row (get-member-by-external-id)
 * - 1 endpoint_api_scopes association (ReadMemberData)
 * - 3 endpoint_api_authz_groups rows (super-admins, administrators, summit-front-end-administrators)
 *
 * All INSERTs are idempotent via WHERE NOT EXISTS.
 */
final class Version20260410172200 extends AbstractMigration
{
    use APIEndpointsMigrationHelper;

    private const API_NAME = 'members';
    private const ENDPOINT_NAME = 'get-member-by-external-id';
    private const ENDPOINT_ROUTE = '/api/v1/members/external/{external_id}';

    public function getDescription(): string
    {
        return 'Seed get-member-by-external-id endpoint with scope and authz groups';
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $scope = MemberScopes::ReadMemberData;

        // 1. Insert the endpoint
        $this->addSql($this->insertEndpoint(
            self::API_NAME,
            self::ENDPOINT_NAME,
            self::ENDPOINT_ROUTE,
            'GET'
        ));

        // 2. Insert endpoint_api_scopes association
        $this->addSql($this->insertEndpointScope(self::API_NAME, self::ENDPOINT_NAME, $scope));

        // 3. Insert endpoint_api_authz_groups
        $authzGroups = [
            IGroup::SuperAdmins,
            IGroup::Administrators,
            IGroup::SummitAdministrators,
        ];

        foreach ($authzGroups as $groupSlug) {
            $this->addSql($this->insertEndpointAuthzGroup(self::API_NAME, self::ENDPOINT_NAME, $groupSlug));
        }
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        $scope = MemberScopes::ReadMemberData;

        // Reverse order: authz groups → endpoint scopes → endpoint
        $authzGroups = [
            IGroup::SuperAdmins,
            IGroup::Administrators,
            IGroup::SummitAdministrators,
        ];

        foreach ($authzGroups as $groupSlug) {
            $this->addSql($this->deleteEndpointAuthzGroup(self::API_NAME, self::ENDPOINT_NAME, $groupSlug));
        }

        $this->addSql($this->deleteScopesEndpoints(self::API_NAME, [$scope]));
        $this->addSql($this->deleteEndpoint(self::API_NAME, self::ENDPOINT_NAME));
    }
}
