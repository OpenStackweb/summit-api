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

/**
 * Migration to add sponsor authz groups to attendee endpoints.
 *
 * Adds:
 * - 4 endpoint_api_authz_groups rows (sponsors and sponsors-external-users for get-attendees and get-attendees-csv)
 *
 * All INSERTs are idempotent via WHERE NOT EXISTS.
 */
final class Version20260423170000 extends AbstractMigration
{
    use APIEndpointsMigrationHelper;

    private const API_NAME = 'summits';
    private const ENDPOINT_GET_ATTENDEES = 'get-attendees';
    private const ENDPOINT_GET_ATTENDEES_CSV = 'get-attendees-csv';

    public function getDescription(): string
    {
        return 'Add sponsor authz groups to get-attendees and get-attendees-csv endpoints';
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $endpoints = [
            self::ENDPOINT_GET_ATTENDEES,
            self::ENDPOINT_GET_ATTENDEES_CSV,
        ];

        $authzGroups = [
            IGroup::Sponsors,
            IGroup::SponsorExternalUsers,
        ];

        foreach ($endpoints as $endpointName) {
            foreach ($authzGroups as $groupSlug) {
                $this->addSql($this->insertEndpointAuthzGroup(self::API_NAME, $endpointName, $groupSlug));
            }
        }
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        $endpoints = [
            self::ENDPOINT_GET_ATTENDEES,
            self::ENDPOINT_GET_ATTENDEES_CSV,
        ];

        $authzGroups = [
            IGroup::Sponsors,
            IGroup::SponsorExternalUsers,
        ];

        foreach ($endpoints as $endpointName) {
            foreach ($authzGroups as $groupSlug) {
                $this->addSql($this->deleteEndpointAuthzGroup(self::API_NAME, $endpointName, $groupSlug));
            }
        }
    }
}
