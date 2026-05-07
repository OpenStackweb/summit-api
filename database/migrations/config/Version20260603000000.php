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
 * Seed get-submitters-activities-count and get-speakers-activities-count endpoints.
 *
 * Both endpoints are GET-only, scoped to ReadSummitData/ReadAllSummitData, and
 * restricted to SuperAdmins, Administrators, and SummitAdministrators groups.
 * Idempotent via WHERE NOT EXISTS in APIEndpointsMigrationHelper.
 */
final class Version20260603000000 extends AbstractMigration
{
    use APIEndpointsMigrationHelper;

    private const API_NAME = 'summits';

    private const SUBMITTERS_ENDPOINT   = 'get-submitters-activities-count';
    private const SUBMITTERS_ROUTE      = '/api/v1/summits/{id}/submitters/all/events/count';

    private const SPEAKERS_ENDPOINT     = 'get-speakers-activities-count';
    private const SPEAKERS_ROUTE        = '/api/v1/summits/{id}/speakers/all/events/count';

    private const AUTHZ_GROUPS = [
        IGroup::SuperAdmins,
        IGroup::Administrators,
        IGroup::SummitAdministrators,
    ];

    public function getDescription(): string
    {
        return 'Seed get-submitters-activities-count and get-speakers-activities-count endpoints';
    }

    public function up(Schema $schema): void
    {
        foreach ([self::SUBMITTERS_ENDPOINT => self::SUBMITTERS_ROUTE, self::SPEAKERS_ENDPOINT => self::SPEAKERS_ROUTE] as $name => $route) {
            $this->addSql($this->insertEndpoint(self::API_NAME, $name, $route, 'GET'));

            $this->addSql($this->insertEndpointScope(self::API_NAME, $name, SummitScopes::ReadSummitData));
            $this->addSql($this->insertEndpointScope(self::API_NAME, $name, SummitScopes::ReadAllSummitData));

            foreach (self::AUTHZ_GROUPS as $group) {
                $this->addSql($this->insertEndpointAuthzGroup(self::API_NAME, $name, $group));
            }
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql($this->deleteEndpoint(self::API_NAME, self::SUBMITTERS_ENDPOINT));
        $this->addSql($this->deleteEndpoint(self::API_NAME, self::SPEAKERS_ENDPOINT));
    }
}
