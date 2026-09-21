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
 * Register the ReadSpeakersDataEmail scope and grant it on the existing
 * get-speaker-by-summit endpoint (GET /api/v1/summits/{id}/speakers/{speaker_id}),
 * so a service account can request speaker email data without the endpoint's other
 * scopes changing.
 *
 * Idempotent via WHERE NOT EXISTS in APIEndpointsMigrationHelper.
 */
final class Version20260921120000 extends AbstractMigration
{
    use APIEndpointsMigrationHelper;

    private const API_NAME = 'summits';
    private const ENDPOINT_NAME = 'get-speaker-by-summit';

    public function getDescription(): string
    {
        return 'Register ReadSpeakersDataEmail scope and grant it on get-speaker-by-summit endpoint.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql($this->insertApiScope(
            self::API_NAME,
            SummitScopes::ReadSpeakersDataEmail,
            'Read Speakers Email',
            'Grants read access for Speakers Email'
        ));

        $this->addSql($this->insertEndpointScope(
            self::API_NAME,
            self::ENDPOINT_NAME,
            SummitScopes::ReadSpeakersDataEmail
        ));
    }

    public function down(Schema $schema): void
    {
        $this->addSql($this->deleteScopesEndpoints(self::API_NAME, [SummitScopes::ReadSpeakersDataEmail]));
        $this->addSql($this->deleteApiScopes(self::API_NAME, [SummitScopes::ReadSpeakersDataEmail]));
    }
}
