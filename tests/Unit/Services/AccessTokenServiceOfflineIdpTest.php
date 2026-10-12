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

use App\Models\ResourceServer\AccessTokenService;
use App\Models\ResourceServer\IAccessTokenService;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use libs\oauth2\InvalidGrantTypeException;
use libs\utils\ICacheService;
use Tests\TestCase;

/**
 * Class AccessTokenServiceOfflineIdpTest
 * A 503 from the IDP says nothing about the token itself, so the token must only be
 * flagged as revoked for a short time. A 400 invalid_token is a real revocation.
 * The IDP is a local PHP built-in server answering a fixed response.
 */
final class AccessTokenServiceOfflineIdpTest extends TestCase
{
    /**
     * @var resource|null
     */
    private $server = null;

    /**
     * @var string|null
     */
    private $router_file = null;

    /**
     * @var string
     */
    private $token_value;

    /**
     * @var string
     */
    private $revoked_key;

    /**
     * @var ICacheService
     */
    private $cache;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cache = App::make(ICacheService::class);
        $this->token_value = 'offline-idp-test-' . bin2hex(random_bytes(8));
        $this->revoked_key = md5($this->token_value) . '.revoked';
    }

    protected function tearDown(): void
    {
        if (!is_null($this->server)) {
            proc_terminate($this->server);
            proc_close($this->server);
        }
        if (!is_null($this->router_file)) {
            @unlink($this->router_file);
        }
        $this->cache->delete($this->revoked_key);
        $this->cache->delete(md5($this->token_value));
        parent::tearDown();
    }

    /**
     * Starts a fake IDP that answers every request with the same status, headers and body.
     * @param int $status
     * @param array $headers
     * @param string $body
     */
    private function startFakeIdp(int $status, array $headers, string $body): void
    {
        $listener = stream_socket_server('tcp://127.0.0.1:0', $errno, $errstr);
        $this->assertNotFalse($listener, "could not reserve a port: $errstr");
        $port = (int)explode(':', stream_socket_get_name($listener, false))[1];
        fclose($listener);

        $base = tempnam(sys_get_temp_dir(), 'fake_idp_');
        $this->router_file = $base . '.php';
        // tempnam() already created $base: rename it so no empty file is left behind
        rename($base, $this->router_file);
        file_put_contents(
            $this->router_file,
            sprintf(
                "<?php\nhttp_response_code(%d);\nforeach (%s as \$h) { header(\$h); }\necho %s;\n",
                $status,
                var_export($headers, true),
                var_export($body, true)
            )
        );

        $this->server = proc_open(
            [PHP_BINARY, '-S', "127.0.0.1:$port", $this->router_file],
            [0 => ['pipe', 'r'], 1 => ['file', '/dev/null', 'w'], 2 => ['file', '/dev/null', 'w']],
            $pipes
        );

        $ready = false;
        for ($i = 0; $i < 30 && !$ready; $i++) {
            $conn = @fsockopen('127.0.0.1', $port, $e, $s, 0.1);
            if ($conn) {
                fclose($conn);
                $ready = true;
            } else {
                usleep(100000);
            }
        }
        $this->assertTrue($ready, 'the fake IDP did not start');

        Config::set('app.openstackid_client_id', 'test-client');
        Config::set('app.openstackid_client_secret', 'test-secret');
        Config::set('app.openstackid_base_url', "http://127.0.0.1:$port");
    }

    /**
     * @return bool true when the service rejected the token
     */
    private function tokenIsRejected(): bool
    {
        try {
            App::make(IAccessTokenService::class)->get($this->token_value);
        } catch (InvalidGrantTypeException $ex) {
            return true;
        }
        return false;
    }

    public function testOfflineIdpOnlyFlagsTheTokenForAShortTime()
    {
        $this->startFakeIdp(503, ['Retry-After: 0', 'Content-Type: text/plain'], 'offline');

        $this->assertTrue($this->tokenIsRejected());

        $ttl = $this->cache->ttl($this->revoked_key);
        $this->assertGreaterThan(0, $ttl, 'the flag must expire, ttl=' . $ttl);
        $this->assertLessThanOrEqual(30, $ttl);
    }

    public function testOfflineIdpDoesNotDowngradeAPermanentRevocation()
    {
        // get() returns before introspecting when the flag already exists, so the race (a 400 writing
        // the permanent flag while a 503 is in flight) is reproduced by calling the introspection directly
        $this->startFakeIdp(503, ['Retry-After: 0', 'Content-Type: text/plain'], 'offline');
        $this->cache->setSingleValue($this->revoked_key, md5($this->token_value));

        $introspect = new \ReflectionMethod(AccessTokenService::class, 'doIntrospectionRequest');
        $introspect->setAccessible(true);
        try {
            $introspect->invoke(App::make(IAccessTokenService::class), $this->token_value);
            $this->fail('expected an InvalidGrantTypeException');
        } catch (InvalidGrantTypeException $ex) {
            // expected
        }

        $this->assertSame(-1, $this->cache->ttl($this->revoked_key), 'a permanent revocation must not be downgraded');
    }

    public function testInvalidTokenFromIdpStaysFlagged()
    {
        $this->startFakeIdp(400, ['Content-Type: application/json'], '{"error":"invalid_token"}');

        $this->assertTrue($this->tokenIsRejected());
        $this->assertTrue($this->cache->exists($this->revoked_key));
    }
}
