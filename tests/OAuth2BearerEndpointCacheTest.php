<?php namespace Tests;
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

use App\Http\Middleware\OAuth2BearerAccessTokenRequestValidator;
use App\Models\ResourceServer\ApiEndpoint;
use App\Models\ResourceServer\IAccessTokenService;
use App\Models\ResourceServer\IApiEndpointRepository;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use models\oauth2\IResourceServerContext;
use Mockery;

/**
 * Class OAuth2BearerEndpointCacheTest
 * Covers the cache of the endpoint lookup done by the bearer token validator.
 */
class OAuth2BearerEndpointCacheTest extends TestCase
{
    private const Route  = '/api/v1/summits/{id}';
    private const Method = 'GET';

    protected function setUp(): void
    {
        parent::setUp();
        Config::set('cache.default', 'array');
        Cache::flush();
        Config::set('cache_api_response.api_endpoint_lifetime', 3600);
    }

    protected function tearDown(): void
    {
        Cache::flush();
        Mockery::close();
        parent::tearDown();
    }

    private function endpoint(bool $active, array $scopes): ApiEndpoint
    {
        $endpoint = Mockery::mock(ApiEndpoint::class);
        $endpoint->shouldReceive('isActive')->andReturn($active);
        $endpoint->shouldReceive('getScopesNames')->andReturn($scopes);
        return $endpoint;
    }

    private function lookup($repository): array
    {
        $validator = new OAuth2BearerAccessTokenRequestValidator(
            Mockery::mock(IResourceServerContext::class),
            $repository,
            Mockery::mock(IAccessTokenService::class)
        );
        $m = new \ReflectionMethod($validator, 'getEndpointInfo');
        $m->setAccessible(true);
        return $m->invoke($validator, self::Route, self::Method);
    }

    public function testCacheMissPopulatesAndHitSkipsRepository(): void
    {
        $repo = Mockery::mock(IApiEndpointRepository::class);
        $repo->shouldReceive('getApiEndpointByUrlAndMethod')
            ->once()
            ->with(self::Route, self::Method)
            ->andReturn($this->endpoint(true, ['scope-a', 'scope-b']));

        $expected = ['exists' => true, 'active' => true, 'scopes' => ['scope-a', 'scope-b']];
        $this->assertSame($expected, $this->lookup($repo));
        $this->assertSame($expected, $this->lookup($repo));
    }

    public function testInactiveEndpointIsReportedInactive(): void
    {
        $repo = Mockery::mock(IApiEndpointRepository::class);
        $repo->shouldReceive('getApiEndpointByUrlAndMethod')
            ->once()
            ->andReturn($this->endpoint(false, ['scope-a']));

        $this->assertFalse($this->lookup($repo)['active']);
        $this->assertFalse($this->lookup($repo)['active']);
    }

    public function testUnknownEndpointIsCachedAsMissing(): void
    {
        $repo = Mockery::mock(IApiEndpointRepository::class);
        $repo->shouldReceive('getApiEndpointByUrlAndMethod')->once()->andReturn(null);

        $this->assertFalse($this->lookup($repo)['exists']);
        $this->assertFalse($this->lookup($repo)['exists']);
    }

    public function testLifetimeZeroDisablesCache(): void
    {
        Config::set('cache_api_response.api_endpoint_lifetime', 0);
        $repo = Mockery::mock(IApiEndpointRepository::class);
        $repo->shouldReceive('getApiEndpointByUrlAndMethod')
            ->twice()
            ->andReturn($this->endpoint(true, ['scope-a']));

        $this->lookup($repo);
        $this->lookup($repo);
        $this->addToAssertionCount(Mockery::getContainer()->mockery_getExpectationCount());
    }

    public function testFlushForcesNewLookup(): void
    {
        $repo = Mockery::mock(IApiEndpointRepository::class);
        $repo->shouldReceive('getApiEndpointByUrlAndMethod')
            ->twice()
            ->andReturn($this->endpoint(true, ['scope-a']));

        $this->lookup($repo);
        OAuth2BearerAccessTokenRequestValidator::flushEndpointCache();
        $this->lookup($repo);
        $this->addToAssertionCount(Mockery::getContainer()->mockery_getExpectationCount());
    }
}
