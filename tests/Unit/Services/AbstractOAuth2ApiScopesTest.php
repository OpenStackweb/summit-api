<?php namespace Tests\Unit\Services;
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
use App\Services\Apis\MailApi;
use Illuminate\Support\Facades\Config;
use libs\utils\ICacheService;
use Mockery;
use Tests\TestCase;

/**
 * Class AbstractOAuth2ApiScopesTest
 * Regression test for implode() TypeError when scopes config is a string.
 * @see https://github.com/OpenStackweb/summit-api/issues/XXX
 * @package Tests\Unit\Services
 */
class AbstractOAuth2ApiScopesTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Regression test: getAccessToken() must not throw TypeError when
     * scopes is a string from env(). Previously, implode(' ', $scopes)
     * crashed because $scopes was a string, not an array.
     *
     * @dataProvider scopesProvider
     */
    public function testGetAccessTokenHandlesVariousScopesTypes($scopeValue, string $description)
    {
        Config::set('idp.authorization_endpoint', 'https://idp.test/authorize');
        Config::set('idp.token_endpoint', 'https://idp.test/token');
        Config::set('mail.service_base_url', 'https://mail.test');
        Config::set('mail.service_client_id', 'test-client');
        Config::set('mail.service_client_secret', 'test-secret');
        Config::set('mail.service_client_scopes', $scopeValue);
        Config::set('curl.timeout', 1);
        Config::set('curl.allow_redirects', false);
        Config::set('curl.verify_ssl_cert', true);

        $cacheService = Mockery::mock(ICacheService::class);
        $cacheService->shouldReceive('getSingleValue')->andReturn(null);

        $api = new MailApi($cacheService);

        $reflection = new \ReflectionMethod($api, 'getAccessToken');
        $reflection->setAccessible(true);

        try {
            $reflection->invoke($api);
        } catch (\TypeError $e) {
            $this->fail("TypeError thrown with {$description}: " . $e->getMessage());
        } catch (\Exception $e) {
            // Connection/HTTP errors are expected since IDP is not reachable.
            // The critical assertion is that no TypeError was thrown from implode().
            $this->assertNotInstanceOf(
                \TypeError::class,
                $e,
                "No TypeError should occur with {$description}"
            );
        }
    }

    public static function scopesProvider(): array
    {
        return [
            'string scopes (space-separated)' => ['scope1 scope2', 'space-separated string scopes'],
            'single string scope'             => ['payment-profile/read', 'single string scope'],
            'null scopes'                     => [null, 'null scopes'],
            'empty string scopes'             => ['', 'empty string scopes'],
        ];
    }
}
