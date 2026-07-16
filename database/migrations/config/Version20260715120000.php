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

use App\Security\SummitScopes;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Seed the get-overflow-published-events endpoint.
 *
 * Adds:
 * - 1 api_scopes row (ReadOverflowEvents) - dedicated scope so this endpoint's Mux
 *   playback tokens are not gated by the broad ReadSummitData/ReadAllSummitData scopes
 *   shared by unrelated read endpoints
 * - 1 api_endpoints row (get-overflow-published-events)
 * - 1 endpoint_api_scopes association (ReadOverflowEvents)
 *
 * All INSERTs are idempotent via WHERE NOT EXISTS.
 */
final class Version20260715120000 extends AbstractMigration
{
    use APIEndpointsMigrationHelper;

    private const API_NAME = 'summits';
    private const ENDPOINT_NAME = 'get-overflow-published-events';
    private const ENDPOINT_ROUTE = '/api/v1/summits/{id}/events/all/published/occupancy/overflow';

    public function getDescription(): string
    {
        return 'Seed get-overflow-published-events endpoint with a dedicated read scope.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql($this->insertApiScope(
            self::API_NAME,
            SummitScopes::ReadOverflowEvents,
            'Read Summit Overflow Events Data',
            'Grants read only access to published summit events currently in OVERFLOW occupancy, including overflow streaming URLs and tokens'
        ));

        $this->registerEndpoint(
            self::API_NAME,
            self::ENDPOINT_NAME,
            self::ENDPOINT_ROUTE,
            'GET',
            [
                SummitScopes::ReadOverflowEvents,
            ]
        );
    }

    public function down(Schema $schema): void
    {
        $this->unregisterEndpoint(self::API_NAME, self::ENDPOINT_NAME);
        $this->addSql($this->deleteApiScopes(self::API_NAME, [SummitScopes::ReadOverflowEvents]));
    }
}
