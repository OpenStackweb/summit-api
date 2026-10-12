<?php namespace Tests\Unit;
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
use App\Models\ResourceServer\IAccessTokenService;
use App\Models\ResourceServer\IApiEndpoint;
use App\Models\ResourceServer\IApiEndpointRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use models\oauth2\IResourceServerContext;
use Tests\TestCase;

/**
 * Subclass that bypasses getallheaders()/SERVER parsing by returning a fixed header map.
 * $fixedHeaders must be assigned before parent::__construct() because the parent ctor
 * calls $this->getHeaders() immediately.
 */
class TestableBearerValidator extends OAuth2BearerAccessTokenRequestValidator
{
    private array $fixedHeaders;

    public function __construct(
        array $fixedHeaders,
        IResourceServerContext $context,
        IApiEndpointRepository $endpoint_repository,
        IAccessTokenService $token_service
    ) {
        $this->fixedHeaders = $fixedHeaders;
        parent::__construct($context, $endpoint_repository, $token_service);
    }

    protected function getHeaders(): array
    {
        return $this->fixedHeaders;
    }
}

/**
 * Class OAuth2BearerAccessTokenRequestValidatorTest
 *
 * Verifies that the JS_CLIENT origin check correctly accepts normalized URLs
 * and rejects bare hostnames or requests with no Origin header.
 */
final class OAuth2BearerAccessTokenRequestValidatorTest extends TestCase
{
    private const TOKEN           = 'test-bearer-token';
    private const HOST            = 'example.com';
    private const ALLOWED_ORIGINS = 'https://example.com https://foo.bar';

    private IResourceServerContext  $context;
    private IApiEndpointRepository  $endpointRepo;
    private IAccessTokenService     $tokenService;

    protected function setUp(): void
    {
        parent::setUp();

        Route::get('/api/test', fn() => 'ok');

        $this->context = $this->createMock(IResourceServerContext::class);

        $endpoint = $this->createMock(IApiEndpoint::class);
        $endpoint->method('isActive')->willReturn(true);
        $endpoint->method('getScopesNames')->willReturn(['openid']);

        $this->endpointRepo = $this->createMock(IApiEndpointRepository::class);
        $this->endpointRepo->method('getApiEndpointByUrlAndMethod')->willReturn($endpoint);

        // AccessToken is final, so use an anonymous stub instead of createMock().
        $tokenStub = new class {
            public function getLifetime()            { return 3600; }
            public function getAudience()            { return 'example.com'; }
            public function getApplicationType()     { return 'JS_CLIENT'; }
            public function getAllowedOrigins(): ?string { return 'https://example.com https://foo.bar'; }
            public function getScope()               { return 'openid'; }
            public function getClientId()            { return 'test-client'; }
            public function getUserId(): ?int        { return null; }
            public function getAllowedReturnUris()   { return ''; }
        };

        $this->tokenService = $this->createMock(IAccessTokenService::class);
        $this->tokenService->method('get')->willReturn($tokenStub);

        Log::shouldReceive('debug')->zeroOrMoreTimes();
        Log::shouldReceive('warning')->zeroOrMoreTimes();
        Log::shouldReceive('info')->zeroOrMoreTimes();
        Log::shouldReceive('error')->zeroOrMoreTimes();
    }

    // -------------------------------------------------------------------------

    private function buildValidator(string $originHeader = ''): TestableBearerValidator
    {
        $headers = ['authorization' => 'Bearer ' . self::TOKEN];
        if ($originHeader !== '') {
            $headers['origin'] = $originHeader;
        }
        return new TestableBearerValidator(
            $headers,
            $this->context,
            $this->endpointRepo,
            $this->tokenService
        );
    }

    private function buildRequest(string $originHeader = ''): Request
    {
        $server = ['HTTP_HOST' => self::HOST];
        if ($originHeader !== '') {
            $server['HTTP_ORIGIN'] = $originHeader;
        }
        return Request::create('/api/test', 'GET', [], [], [], $server);
    }

    private function next(): \Closure
    {
        return fn($req) => new JsonResponse(['ok' => true], 200);
    }

    // -------------------------------------------------------------------------

    public function test_exact_origin_url_is_accepted(): void
    {
        $this->context->expects($this->once())->method('setAuthorizationContext');

        $response = $this->buildValidator('https://example.com')
            ->handle($this->buildRequest('https://example.com'), $this->next());

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_trailing_slash_origin_is_accepted(): void
    {
        $this->context->expects($this->once())->method('setAuthorizationContext');

        $response = $this->buildValidator('https://example.com/')
            ->handle($this->buildRequest('https://example.com/'), $this->next());

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_bare_hostname_without_scheme_is_rejected(): void
    {
        $this->context->expects($this->never())->method('setAuthorizationContext');

        $response = $this->buildValidator('example')
            ->handle($this->buildRequest('example'), $this->next());

        $this->assertEquals(403, $response->getStatusCode());
    }

    public function test_missing_origin_is_rejected(): void
    {
        $this->context->expects($this->never())->method('setAuthorizationContext');

        $response = $this->buildValidator()
            ->handle($this->buildRequest(), $this->next());

        $this->assertEquals(403, $response->getStatusCode());
    }
}
