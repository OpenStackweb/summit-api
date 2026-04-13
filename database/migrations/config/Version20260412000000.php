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
use App\Security\SummitScopes;

/**
 * Seed the discover-promo-codes endpoint.
 *
 * Adds the endpoint row plus its two scope associations (ReadSummitData,
 * ReadAllSummitData). No authz groups — the endpoint is authenticated-user
 * scoped and serves the caller their own qualifying codes.
 *
 * Idempotent via WHERE NOT EXISTS in APIEndpointsMigrationHelper.
 */
final class Version20260412000000 extends AbstractMigration
{
    use APIEndpointsMigrationHelper;

    private const API_NAME = 'summits';
    private const ENDPOINT_NAME = 'discover-promo-codes';
    private const ENDPOINT_ROUTE = '/api/v1/summits/{id}/promo-codes/all/discover';

    public function getDescription(): string
    {
        return 'Seed discover-promo-codes endpoint with ReadSummitData/ReadAllSummitData scopes';
    }

    public function up(Schema $schema): void
    {
        $this->addSql($this->insertEndpoint(
            self::API_NAME,
            self::ENDPOINT_NAME,
            self::ENDPOINT_ROUTE,
            'GET'
        ));

        $this->addSql($this->insertEndpointScope(
            self::API_NAME,
            self::ENDPOINT_NAME,
            SummitScopes::ReadSummitData
        ));

        $this->addSql($this->insertEndpointScope(
            self::API_NAME,
            self::ENDPOINT_NAME,
            SummitScopes::ReadAllSummitData
        ));
    }

    public function down(Schema $schema): void
    {
        $this->addSql($this->deleteEndpoint(self::API_NAME, self::ENDPOINT_NAME));
    }
}
