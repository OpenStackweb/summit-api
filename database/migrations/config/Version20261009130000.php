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
 * Register the sponsor extra question and answer option usage endpoints, the pre-check an admin
 * reads before force deleting.
 *
 * ApiEndpointsSeeder covers fresh installs only -- the k8s deploy flow does not re-run
 * seeders -- and OAuth2BearerAccessTokenRequestValidator rejects any route missing from
 * api_endpoints with a 400 before the controller runs. A deployed environment therefore
 * needs these rows inserted by migration or the endpoints are unreachable.
 *
 * The authz groups are a GLOBAL membership check (auth.user); the controller then requires an
 * admin of the summit (Super Admins, Administrators, or Summit Administrators allowed on it).
 *
 * DEPLOY ORDER: this migration must land with or before the application.
 *
 * Idempotent via WHERE NOT EXISTS inside the helper.
 */
final class Version20261009130000 extends AbstractMigration
{
    use APIEndpointsMigrationHelper;

    private const API_NAME = 'summits';

    private const ENDPOINTS = [
        [
            'name' => 'get-sponsor-extra-question-usage',
            'route' => '/api/v1/summits/{id}/sponsors/{sponsor_id}/extra-questions/{extra_question_id}/usage',
            'http_method' => 'GET',
            'scopes' => [
                SummitScopes::ReadSummitData,
                SummitScopes::ReadAllSummitData,
                SummitScopes::ReadSponsorExtraQuestions,
            ],
            'authz_groups' => [
                IGroup::SuperAdmins,
                IGroup::Administrators,
                IGroup::SummitAdministrators,
            ],
        ],
        [
            'name' => 'get-sponsor-extra-question-value-usage',
            'route' => '/api/v1/summits/{id}/sponsors/{sponsor_id}/extra-questions/{extra_question_id}/values/{value_id}/usage',
            'http_method' => 'GET',
            'scopes' => [
                SummitScopes::ReadSummitData,
                SummitScopes::ReadAllSummitData,
                SummitScopes::ReadSponsorExtraQuestions,
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
        return 'Register the sponsor extra question and answer option usage endpoints.';
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
