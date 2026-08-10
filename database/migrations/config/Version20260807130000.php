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
 * Register the two per-activity CFP reopen endpoints.
 *
 * ApiEndpointsSeeder covers fresh installs only -- the k8s deploy flow does not re-run
 * seeders -- and OAuth2BearerAccessTokenRequestValidator rejects any route missing from
 * api_endpoints with a 400 before the controller runs. A deployed environment therefore
 * needs these rows inserted by migration or the endpoints are unreachable.
 *
 * Scopes are the same OR-trio the sibling submission endpoints use; validation is any-of,
 * so this admits existing Show Admin tokens.
 *
 * The authz groups back the auth.user middleware on both routes, and mirror update-event's list
 * minus the track-chair roles, which have no business reopening a submission window. They are a
 * GLOBAL membership check; the summit-scoped admin check still runs in the controller.
 *
 * DEPLOY ORDER: this migration must land with or before the application. auth.user reads these
 * rows, and an endpoint registered without them 403s every authenticated member.
 *
 * Idempotent via WHERE NOT EXISTS inside the helper.
 */
final class Version20260807130000 extends AbstractMigration
{
    use APIEndpointsMigrationHelper;

    private const API_NAME = 'summits';

    private const ENDPOINTS = [
        [
            'name' => 'reopen-presentation-submission-period',
            'route' => '/api/v1/summits/{id}/presentations/{presentation_id}/submission-period/reopen',
            'http_method' => 'PUT',
            'scopes' => [
                SummitScopes::WriteSummitData,
                SummitScopes::WriteEventData,
                SummitScopes::WritePresentationData,
            ],
            'authz_groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
            ],
        ],
        [
            'name' => 'close-presentation-submission-period',
            'route' => '/api/v1/summits/{id}/presentations/{presentation_id}/submission-period/reopen',
            'http_method' => 'DELETE',
            'scopes' => [
                SummitScopes::WriteSummitData,
                SummitScopes::WriteEventData,
                SummitScopes::WritePresentationData,
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
        return 'Register the per-activity CFP reopen/close endpoints.';
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
