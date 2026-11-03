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

use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Records every value the bearer token middleware hands to the token service.
 */
final class RecordingAccessTokenService extends AccessTokenServiceStub
{
    public array $received = [];

    public function get($token_value)
    {
        $this->received[] = $token_value;
        return parent::get($token_value);
    }
}

/**
 * Credentials that are not a single string must be rejected by the bearer token
 * middleware itself (RFC 6750 section 3.1, invalid_request) and never reach the
 * token service.
 */
final class OAuth2MalformedAccessTokenTest extends ProtectedApiTestCase
{
    public function createApplication()
    {
        $app = parent::createApplication();
        self::$service = new RecordingAccessTokenService();
        return $app;
    }

    private function getCompanies(array $params, array $headers = [])
    {
        return $this->action(
            "GET",
            "OAuth2CompaniesApiController@getAllCompanies",
            $params,
            [],
            [],
            [],
            $headers
        );
    }

    private function assertRejectedAsInvalidRequest($response): void
    {
        $this->assertResponseStatus(400);
        $this->assertSame('invalid_request', json_decode($response->getContent(), true)['error'] ?? null);
        $this->assertStringStartsWith('Bearer', (string)$response->headers->get('WWW-Authenticate'));
        $this->assertSame([], self::$service->received, 'the token service must not be called');
    }

    public function testAccessTokenQueryParameterIsAccepted(): void
    {
        $this->getCompanies(['access_token' => $this->access_token]);

        $this->assertResponseStatus(200);
        $this->assertSame([$this->access_token], self::$service->received);
    }

    public static function nonStringAccessTokens(): array
    {
        return [
            'list'        => [[ 'TEST_ACCESS_TOKEN' ]],
            'two values'  => [[ 'TEST_ACCESS_TOKEN', 'TEST_ACCESS_TOKEN' ]],
            'map'         => [[ 'token' => 'TEST_ACCESS_TOKEN' ]],
            'nested list' => [[ [ 'TEST_ACCESS_TOKEN' ] ]],
        ];
    }

    #[DataProvider('nonStringAccessTokens')]
    public function testNonStringAccessTokenQueryParameterIsRejected(array $access_token): void
    {
        $response = $this->getCompanies(['access_token' => $access_token]);

        $this->assertRejectedAsInvalidRequest($response);
    }

    public function testMissingAccessTokenIsRejected(): void
    {
        $response = $this->getCompanies([]);

        $this->assertRejectedAsInvalidRequest($response);
    }

    public function testEmptyBearerAuthorizationHeaderIsRejected(): void
    {
        $response = $this->getCompanies([], ["HTTP_Authorization" => "Bearer "]);

        $this->assertRejectedAsInvalidRequest($response);
    }
}
