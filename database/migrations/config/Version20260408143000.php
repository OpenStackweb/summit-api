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
use App\Models\Foundation\Main\IGroup;

/**
 * Migration to add sponsor extra-questions OAuth2 scopes and endpoint bindings.
 *
 * This migration replicates seeder changes from ApiScopesSeeder and ApiEndpointsSeeder,
 * allowing existing environments to receive the new scopes/bindings without re-running
 * the full seeder suite.
 *
 * Adds:
 * - 2 api_scopes rows (ReadSponsorExtraQuestions, WriteSponsorExtraQuestions)
 * - 9 endpoint_api_scopes associations across sponsor-extra-questions endpoints
 * - 1 endpoint_api_authz_groups row (sponsors-external-users on get-sponsor-extra-questions)
 *
 * All INSERTs are idempotent via WHERE NOT EXISTS. down() is local-dev only; do not
 * run on environments where additional endpoints may have been bound to these scopes.
 *
 * IMPORTANT: This migration MUST be committed alongside the SummitScopes.php edit that
 * introduces ReadSponsorExtraQuestions and WriteSponsorExtraQuestions constants.
 */
final class Version20260408143000 extends AbstractMigration
{
    use APIEndpointsMigrationHelper;

    private const API_NAME = 'summits';

    public function getDescription(): string
    {
        return 'Add sponsor extra-questions scopes and endpoint bindings';
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        // Resolve class constants at runtime for env-specific scope URIs
        $readScope = SummitScopes::ReadSponsorExtraQuestions;
        $writeScope = SummitScopes::WriteSponsorExtraQuestions;
        $externalGroupSlug = IGroup::SponsorExternalUsers;

        // 1. Insert api_scopes
        $this->addSql($this->insertApiScope(
            self::API_NAME,
            $readScope,
            'Read Summit Sponsor Extra Questions Data',
            'Read Summit Sponsor Extra Questions Data'
        ));
        $this->addSql($this->insertApiScope(
            self::API_NAME,
            $writeScope,
            'Write Summit Sponsor Extra Questions Data',
            'Write Summit Sponsor Extra Questions Data'
        ));

        // 2. Insert endpoint_api_scopes associations
        $associations = [
            ['get-sponsor-extra-questions',           $readScope],
            ['add-sponsor-extra-question',            $writeScope],
            ['get-sponsor-extra-question',            $readScope],
            ['update-sponsor-extra-question',         $writeScope],
            ['delete-sponsor-extra-question',         $writeScope],
            ['get-sponsor-extra-questions-metadata',  $readScope],
            ['add-sponsor-extra-question-value',      $writeScope],
            ['update-sponsor-extra-question-value',   $writeScope],
            ['delete-sponsor-extra-question-value',   $writeScope],
        ];

        foreach ($associations as [$endpointName, $scopeName]) {
            $this->addSql($this->insertEndpointScope(self::API_NAME, $endpointName, $scopeName));
        }

        // 3. Insert endpoint_api_authz_groups
        $this->addSql($this->insertEndpointAuthzGroup(self::API_NAME, 'get-sponsor-extra-questions', $externalGroupSlug));
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        $readScope = SummitScopes::ReadSponsorExtraQuestions;
        $writeScope = SummitScopes::WriteSponsorExtraQuestions;
        $externalGroupSlug = IGroup::SponsorExternalUsers;

        // Reverse order: authz groups → endpoint scopes → api scopes
        $this->addSql($this->deleteEndpointAuthzGroup(self::API_NAME, 'get-sponsor-extra-questions', $externalGroupSlug));
        $this->addSql($this->deleteScopesEndpoints([$readScope, $writeScope]));
        $this->addSql($this->deleteApiScopes(self::API_NAME, [$readScope, $writeScope]));
    }
}
