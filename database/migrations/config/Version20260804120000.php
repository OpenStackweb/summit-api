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
 * Register the ReadAllPresentationMediaUploads scope.
 *
 * Adds 1 api_scopes row and NO endpoint association, on purpose: endpoint scopes are
 * validated with array_intersect (any-of, see OAuth2BearerAccessTokenRequestValidator:193),
 * so associating this scope with an existing endpoint would admit a token holding only
 * this scope to that endpoint - widening access rather than narrowing it. The scope is a
 * privilege modifier over endpoints that already exist, read straight off the token by
 * PresentationSerializer::getMediaUploadsSerializerType(), which never consults
 * endpoint_api_scopes.
 *
 * The registry that actually issues the token is openstackid's oauth2_api_scope. This row
 * is convention and discoverability on the summit-api side; the scope still has to be
 * created there and granted to the content-snapshot client for a token to carry it.
 *
 * Idempotent via WHERE NOT EXISTS.
 */
final class Version20260804120000 extends AbstractMigration
{
    use APIEndpointsMigrationHelper;

    private const API_NAME = 'summits';

    public function getDescription(): string
    {
        return 'Register ReadAllPresentationMediaUploads scope (no endpoint association).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql($this->insertApiScope(
            self::API_NAME,
            SummitScopes::ReadAllPresentationMediaUploads,
            'Read All Presentation Media Uploads',
            'Grants read access to presentation media uploads regardless of display_on_site, for trusted service accounts feeding the content pipeline'
        ));
    }

    public function down(Schema $schema): void
    {
        $this->addSql($this->deleteApiScopes(self::API_NAME, [SummitScopes::ReadAllPresentationMediaUploads]));
    }
}
