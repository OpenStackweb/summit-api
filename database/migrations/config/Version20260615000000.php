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
use App\Models\Foundation\Main\IGroup;
use App\Security\SummitScopes;

/**
 * Seed the five SummitSponsorshipAddOnType CRUD endpoints.
 *
 * Adds:
 * - 5 api_endpoints rows under the 'summits' API
 * - scope associations: ReadSummitData + ReadAllSummitData for GETs, WriteSummitData for writes
 * - authz groups: SuperAdmins, Administrators, SummitAdministrators for all five
 *
 * All INSERTs are idempotent via WHERE NOT EXISTS.
 */
final class Version20260615000000 extends AbstractMigration
{
    use APIEndpointsMigrationHelper;

    private const API_NAME = 'summits';

    private const ENDPOINT_GET_ALL    = 'get-sponsorship-add-on-types';
    private const ENDPOINT_ADD        = 'add-sponsorship-add-on-type';
    private const ENDPOINT_GET_ONE    = 'get-sponsorship-add-on-type';
    private const ENDPOINT_UPDATE     = 'update-sponsorship-add-on-type';
    private const ENDPOINT_DELETE     = 'delete-sponsorship-add-on-type';

    private const ROUTE_COLLECTION    = '/api/v1/summits/all/add-on-types';
    private const ROUTE_ITEM          = '/api/v1/summits/all/add-on-types/{id}';

    private const READ_ENDPOINTS  = [self::ENDPOINT_GET_ALL, self::ENDPOINT_GET_ONE];
    private const WRITE_ENDPOINTS = [self::ENDPOINT_ADD, self::ENDPOINT_UPDATE, self::ENDPOINT_DELETE];

    private const ALL_ENDPOINTS = [
        self::ENDPOINT_GET_ALL,
        self::ENDPOINT_ADD,
        self::ENDPOINT_GET_ONE,
        self::ENDPOINT_UPDATE,
        self::ENDPOINT_DELETE,
    ];

    private const AUTHZ_GROUPS = [
        IGroup::SuperAdmins,
        IGroup::Administrators,
        IGroup::SummitAdministrators,
    ];

    public function getDescription(): string
    {
        return 'Seed SummitSponsorshipAddOnType CRUD endpoints (get-all, add, get, update, delete)';
    }

    public function up(Schema $schema): void
    {
        // Insert endpoint rows
        $this->addSql($this->insertEndpoint(self::API_NAME, self::ENDPOINT_GET_ALL, self::ROUTE_COLLECTION, 'GET'));
        $this->addSql($this->insertEndpoint(self::API_NAME, self::ENDPOINT_ADD,     self::ROUTE_COLLECTION, 'POST'));
        $this->addSql($this->insertEndpoint(self::API_NAME, self::ENDPOINT_GET_ONE, self::ROUTE_ITEM,       'GET'));
        $this->addSql($this->insertEndpoint(self::API_NAME, self::ENDPOINT_UPDATE,  self::ROUTE_ITEM,       'PUT'));
        $this->addSql($this->insertEndpoint(self::API_NAME, self::ENDPOINT_DELETE,  self::ROUTE_ITEM,       'DELETE'));

        // Scope associations
        foreach (self::READ_ENDPOINTS as $endpoint) {
            $this->addSql($this->insertEndpointScope(self::API_NAME, $endpoint, SummitScopes::ReadSummitData));
            $this->addSql($this->insertEndpointScope(self::API_NAME, $endpoint, SummitScopes::ReadAllSummitData));
        }

        foreach (self::WRITE_ENDPOINTS as $endpoint) {
            $this->addSql($this->insertEndpointScope(self::API_NAME, $endpoint, SummitScopes::WriteSummitData));
        }

        // Authz group associations
        foreach (self::ALL_ENDPOINTS as $endpoint) {
            foreach (self::AUTHZ_GROUPS as $group) {
                $this->addSql($this->insertEndpointAuthzGroup(self::API_NAME, $endpoint, $group));
            }
        }
    }

    public function down(Schema $schema): void
    {
        foreach (self::ALL_ENDPOINTS as $endpoint) {
            $this->addSql($this->deleteEndpoint(self::API_NAME, $endpoint));
        }
    }
}
