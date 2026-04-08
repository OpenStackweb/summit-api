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

/**
 * Reusable SQL template helpers for config entity manager migrations.
 *
 * Provides idempotent INSERT and DELETE templates for api_endpoints, api_scopes,
 * endpoint_api_scopes, and endpoint_api_authz_groups tables.
 *
 * Usage:
 *   final class VersionXXX extends AbstractMigration
 *   {
 *       use APIEndpointsMigrationHelper;
 *
 *       public function up(Schema $schema): void
 *       {
 *           $this->addSql($this->insertApiScope('summits', $scopeName, $desc, $desc));
 *           $this->addSql($this->insertEndpointScope('summits', $endpointName, $scopeName));
 *           $this->addSql($this->insertEndpointAuthzGroup('summits', $endpointName, $groupSlug));
 *       }
 *   }
 */
trait APIEndpointsMigrationHelper
{
    /**
     * Generate idempotent INSERT for api_endpoints table.
     *
     * @param string $apiName       API identifier (e.g., 'summits')
     * @param string $endpointName  Endpoint identifier (e.g., 'get-sponsor-extra-questions')
     * @param string $route         Route pattern (e.g., '/api/v1/summits/{id}/sponsors/{sponsor_id}/extra-questions')
     * @param string $httpMethod    Plain HTTP method string (e.g., 'GET', 'POST', 'PUT', 'DELETE')
     * @param bool   $active        Whether the endpoint is active (default: true)
     * @param bool   $allowCors     Whether to allow CORS (default: true, matches seedApiEndpoints behavior)
     * @param bool   $allowCredentials Whether to allow credentials (default: true, matches seedApiEndpoints behavior)
     * @return string SQL INSERT statement
     */
    protected function insertEndpoint(
        string $apiName,
        string $endpointName,
        string $route,
        string $httpMethod,
        bool $active = true,
        bool $allowCors = true,
        bool $allowCredentials = true
    ): string {
        $activeInt = $active ? 1 : 0;
        $corsInt = $allowCors ? 1 : 0;
        $credentialsInt = $allowCredentials ? 1 : 0;

        return <<<SQL
INSERT INTO api_endpoints (api_id, name, route, http_method, active, allow_cors, allow_credentials, created_at, updated_at)
SELECT a.id, '{$endpointName}', '{$route}', '{$httpMethod}', {$activeInt}, {$corsInt}, {$credentialsInt}, NOW(), NOW()
FROM apis a
WHERE a.name = '{$apiName}'
  AND NOT EXISTS (SELECT 1 FROM api_endpoints e WHERE e.api_id = a.id AND e.name = '{$endpointName}');
SQL;
    }

    /**
     * Generate DELETE for api_endpoints table.
     *
     * @param string $apiName      API identifier
     * @param string $endpointName Endpoint identifier to delete
     * @return string SQL DELETE statement
     */
    protected function deleteEndpoint(string $apiName, string $endpointName): string
    {
        return <<<SQL
DELETE e FROM api_endpoints e
INNER JOIN apis a ON a.id = e.api_id
WHERE a.name = '{$apiName}'
  AND e.name = '{$endpointName}';
SQL;
    }

    /**
     * Generate idempotent INSERT for api_scopes table.
     *
     * @param string $apiName       API identifier (e.g., 'summits', 'resource-server')
     * @param string $scopeName     Full scope URI (e.g., 'https://example.com/summits/read')
     * @param string $shortDesc     Short description for the scope
     * @param string $desc          Full description for the scope
     * @param bool   $active        Whether the scope is active (default: true)
     * @param bool   $default       Whether the scope is default (default: false)
     * @param bool   $system        Whether the scope is a system scope (default: false)
     * @return string SQL INSERT statement
     */
    protected function insertApiScope(
        string $apiName,
        string $scopeName,
        string $shortDesc,
        string $desc,
        bool $active = true,
        bool $default = false,
        bool $system = false
    ): string {
        $activeInt = $active ? 1 : 0;
        $defaultInt = $default ? 1 : 0;
        $systemInt = $system ? 1 : 0;

        return <<<SQL
INSERT INTO api_scopes (api_id, name, short_description, description, active, `default`, `system`, created_at, updated_at)
SELECT a.id, '{$scopeName}', '{$shortDesc}', '{$desc}', {$activeInt}, {$defaultInt}, {$systemInt}, NOW(), NOW()
FROM apis a
WHERE a.name = '{$apiName}'
  AND NOT EXISTS (SELECT 1 FROM api_scopes s WHERE s.api_id = a.id AND s.name = '{$scopeName}');
SQL;
    }

    /**
     * Generate idempotent INSERT for endpoint_api_scopes table.
     *
     * Links an endpoint to a scope by their names.
     *
     * @param string $apiName       API identifier (e.g., 'summits')
     * @param string $endpointName  Endpoint identifier (e.g., 'get-sponsor-extra-questions')
     * @param string $scopeName     Full scope URI
     * @return string SQL INSERT statement
     */
    protected function insertEndpointScope(string $apiName, string $endpointName, string $scopeName): string
    {
        return <<<SQL
INSERT INTO endpoint_api_scopes (api_endpoint_id, scope_id, created_at, updated_at)
SELECT e.id, s.id, NOW(), NOW()
FROM api_endpoints e
INNER JOIN apis a ON a.id = e.api_id
INNER JOIN api_scopes s ON s.api_id = a.id
WHERE a.name = '{$apiName}'
  AND e.name = '{$endpointName}'
  AND s.name = '{$scopeName}'
  AND NOT EXISTS (
      SELECT 1 FROM endpoint_api_scopes eas
      WHERE eas.api_endpoint_id = e.id AND eas.scope_id = s.id
  );
SQL;
    }

    /**
     * Generate idempotent INSERT for endpoint_api_authz_groups table.
     *
     * Links an endpoint to an authorization group by slug.
     *
     * @param string $apiName       API identifier (e.g., 'summits')
     * @param string $endpointName  Endpoint identifier
     * @param string $groupSlug     Group slug (e.g., 'sponsors-external-users')
     * @return string SQL INSERT statement
     */
    protected function insertEndpointAuthzGroup(string $apiName, string $endpointName, string $groupSlug): string
    {
        return <<<SQL
INSERT INTO endpoint_api_authz_groups (api_endpoint_id, group_slug, created_at, updated_at)
SELECT e.id, '{$groupSlug}', NOW(), NOW()
FROM api_endpoints e
INNER JOIN apis a ON a.id = e.api_id
WHERE a.name = '{$apiName}'
  AND e.name = '{$endpointName}'
  AND NOT EXISTS (
      SELECT 1 FROM endpoint_api_authz_groups eag
      WHERE eag.api_endpoint_id = e.id AND eag.group_slug = '{$groupSlug}'
  );
SQL;
    }

    /**
     * Generate DELETE for endpoint_api_authz_groups table.
     *
     * @param string $apiName       API identifier
     * @param string $endpointName  Endpoint identifier
     * @param string $groupSlug     Group slug to remove
     * @return string SQL DELETE statement
     */
    protected function deleteEndpointAuthzGroup(string $apiName, string $endpointName, string $groupSlug): string
    {
        return <<<SQL
DELETE eag FROM endpoint_api_authz_groups eag
INNER JOIN api_endpoints e ON e.id = eag.api_endpoint_id
INNER JOIN apis a ON a.id = e.api_id
WHERE a.name = '{$apiName}'
  AND e.name = '{$endpointName}'
  AND eag.group_slug = '{$groupSlug}';
SQL;
    }

    /**
     * Generate DELETE for endpoint_api_scopes table (all associations for given scopes).
     *
     * Constrained by API to prevent removing associations for other APIs that may
     * reuse the same scope URI (api_scopes.name has no global uniqueness constraint).
     *
     * @param string $apiName API identifier (e.g., 'summits')
     * @param array  $scopes  List of scope URIs to remove associations for
     * @return string SQL DELETE statement
     */
    protected function deleteScopesEndpoints(string $apiName, array $scopes): string
    {
        $scopeList = "'" . implode("', '", $scopes) . "'";
        return <<<SQL
DELETE eas FROM endpoint_api_scopes eas
INNER JOIN api_scopes s ON s.id = eas.scope_id
INNER JOIN apis a ON a.id = s.api_id
WHERE a.name = '{$apiName}'
  AND s.name IN ({$scopeList});
SQL;
    }

    /**
     * Generate DELETE for api_scopes table.
     *
     * @param string $apiName API identifier
     * @param array  $scopes  List of scope URIs to delete
     * @return string SQL DELETE statement
     */
    protected function deleteApiScopes(string $apiName, array $scopes): string
    {
        $scopeList = "'" . implode("', '", $scopes) . "'";
        return <<<SQL
DELETE s FROM api_scopes s
INNER JOIN apis a ON a.id = s.api_id
WHERE a.name = '{$apiName}'
  AND s.name IN ({$scopeList});
SQL;
    }
}
