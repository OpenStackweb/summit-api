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
 * Register the CFP reopen notification endpoint.
 *
 * ApiEndpointsSeeder covers fresh installs only -- the k8s deploy flow does not re-run
 * seeders -- and OAuth2BearerAccessTokenRequestValidator rejects any route missing from
 * api_endpoints with a 400 before the controller runs. A deployed environment therefore
 * needs this row inserted by migration or the endpoint is unreachable.
 *
 * Scopes and authz_groups mirror the reopen/close pair registered by Version20260807130000.
 *
 * Rate limiting for this endpoint (unlike reopen/close, which are idempotent state changes,
 * this one sends mail to real people and is re-sendable by design) is enforced via the
 * `rate.limit:30,3600` middleware on the route itself (routes/api_v1.php), NOT via the
 * api_endpoints.rate_limit/rate_limit_decay columns -- RateLimitMiddleware.php's block that
 * would read those columns off the matched endpoint is commented out (dead code), so setting
 * them here would have no runtime effect. Route-level rate.limit is the only mechanism this
 * codebase actually enforces (precedent: routes/api_v1.php's `discover` and
 * `preValidatePromoCode` promo-code routes).
 *
 * The `3600` is SECONDS and is deliberate: RateLimitMiddleware overrides handle() and passes its
 * $decayMinutes argument straight into RateLimiter::hit($key, $decaySeconds), skipping the
 * `60 * $decayMinutes` conversion Laravel's own ThrottleRequests::handle() performs. So the
 * second rate.limit argument is effectively seconds in this codebase, and `30,60` -- which is
 * what "30 per hour" looks like if you trust the parameter name -- would actually cap at 30 per
 * MINUTE. The two existing `rate.limit:25,1` promo-code routes are 25-per-second for the same
 * reason.
 *
 * DEPLOY ORDER: this migration must land with or before the application. auth.user reads these
 * rows, and an endpoint registered without them 403s every authenticated member.
 *
 * Idempotent: registerEndpoints() guards via WHERE NOT EXISTS inside the helper.
 */
final class Version20260824100000 extends AbstractMigration
{
    use APIEndpointsMigrationHelper;

    private const API_NAME = 'summits';

    private const ENDPOINTS = [
        [
            'name' => 'notify-presentation-submission-period',
            'route' => '/api/v1/summits/{id}/presentations/{presentation_id}/submission-period/reopen/notify',
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
    ];

    public function getDescription(): string
    {
        return 'Register the CFP reopen notification endpoint.';
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
